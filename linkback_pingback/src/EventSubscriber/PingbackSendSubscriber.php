<?php

namespace Drupal\linkback_pingback\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\linkback\Event\LinkbackSendEvent;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class PingbackSendSubscriber.
 *
 * @package Drupal\linkback_pingback
 */
class PingbackSendSubscriber implements EventSubscriberInterface {

  /**
   * Agent.
   *
   * @const string
   */
  // User-agent to use when querying remote sites.
  const UA = 'Drupal Pingback (+http://drupal.org/project/linkback)';

  /**
   * GuzzleHttp\Client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param GuzzleHttp\Client $http_client
   *   GuzzleHttp\Client definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(Client $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['linkback_send'] = ['onLinkbackSend'];

    return $events;
  }

  /**
   * This method is called whenever the linkback_send event is dispatched.
   *
   * @param \Drupal\linkback\Event\LinkbackSendEvent $event
   *   The event that is triggering the process.
   */
  public function onLinkbackSend(LinkbackSendEvent $event) {
    drupal_set_message('Event linkback_send thrown by Subscriber in module linkback_pingback.', 'status', TRUE);
    $this->sendPingback($event->getSource(), $event->getTarget());
  }

  /**
   * Sends the pingback.
   *
   * @param \Drupal\Core\Url $sourceUrl
   *   The source url.
   * @param \Drupal\Core\Url $targetUrl
   *   The target url.
   *
   * @return bool
   *   True if the sending process is ok, else false.
   */
  public function sendPingback(Url $sourceUrl, Url $targetUrl) {
    $source = $sourceUrl->setOption("absolute", TRUE)->toString();
    $target = $targetUrl->setOption("absolute", TRUE)->toString();
    $this->logger->debug('Event linkback_send trying to pingback from %source to %target.', ['%source' => $source, '%target' => $target]);
    if ($xmlrpc_endpoint = $this->getXmlRpcEndpoint($target)) {
      $params = [
        '%source' => $source,
        '%target' => $target,
        '%endpoint' => $xmlrpc_endpoint,
      ];
      $methods = [
        'pingback.ping' => [$source, $target],
      ];
      $result = xmlrpc($xmlrpc_endpoint, $methods, ['headers' => ['User-Agent' => self::UA]]);
      if ($result) {
        $params = [
          '%source' => $source,
          '%target' => $target,
        ];
        return TRUE;
      }
      else {
        $params = [
          '%source' => $source,
          '%target' => $target,
          '@errno' => xmlrpc_errno(),
          '@description' => xmlrpc_error_msg(),
          '%xmlrpc' => $xmlrpc_endpoint,
        ];
        $this->logger->error('Pingback to %target from %source failed.<br />Error @errno: @description in %xmlrpc', $params);
        return FALSE;
      }

    }
    // No XML-RPC endpoint detected; pingback failed.
    return FALSE;
  }

  /**
   * Get the URL of the XML-RPC endpoint that handles pingbacks for a URL.
   *
   * @param string $url
   *   URL of the remote article.
   *
   * @return string|false
   *   Absolute URL of the XML-RPC endpoint, or FALSE if pingback is not
   *   supported.
   */
  protected function getXmlRpcEndpoint($url) {
    try {
      $response = $this->httpClient->get($url, ['headers' => ['Accept' => 'text/plain']]);
      $data = $response->getBody(TRUE);
      $endpoint = $response->getHeader('X-Pingback');
      if ($endpoint) {
        return $endpoint[0];
      }
      $crawler = new Crawler((string) $data);
      $endpoint = $crawler->filter('link[rel="pingback"]')->extract('href');
      if ($endpoint) {
        return $endpoint[0];
      }
    }
    catch (BadResponseException $exception) {
      $response = $exception->getResponse();
      $this->logger->notice('Failed to fetch url %endpoint due to HTTP error "%error"', [
        '%endpoint' => $endpoint,
        '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
      ]);
    }
    catch (RequestException $exception) {
      $this->logger->notice('Failed to fetch url %url due to request error "%error"', [
        '%url' => $url,
        '%error' => $exception->getMessage(),
      ]);
    }
    catch (InvalidArgumentException $exception) {
      $this->logger->notice('Failed to fetch url %endpoint due to invalid argument error "%error"', [
        '%endpoint' => $endpoint,
        '%error' => $exception->getMessage(),
      ]);
    }
    return FALSE;
  }

}

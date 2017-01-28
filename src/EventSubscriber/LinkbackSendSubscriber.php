<?php

/**
 * @file
 */

namespace Drupal\linkback_webmention\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use \Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class LinkbackSendSubscriber.
 *
 * @package Drupal\linkback_webmention
 */
class LinkbackSendSubscriber implements EventSubscriberInterface {

  /**
   * Agent.
   *
   * @const string
   */
  // User-agent to use when querying remote sites.
  const UA = 'Drupal Linkback (+http://drupal.org/project/linkback)';

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
   *   A logger instance.   *    */
  public function __construct(Client $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['linkback_send'] = ['onLinkbackSend'];

    return $events;
  }

  /**
   * This method is called whenever the linkback_send event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onLinkbackSend(Event $event) {
    drupal_set_message('Event linkback_send thrown by Subscriber in module linkback_webmention.', 'status', TRUE);
    $this->sendWebmention($event->getSourceUrl(), $event->getTargetUrl());
  }

  /**
   * Sends the pingback.
   *
   * @param Url $sourceUrl
   * @param Url $targetUrl
   */
  public function sendWebmention($sourceUrl, $targetUrl) {
    $source = $sourceUrl->setOption("absolute", TRUE)->toString();
    $target = $targetUrl->setOption("absolute", TRUE)->toString();
//    if ($xmlrpc_endpoint = $this->getXmlRpcEndpoint($target)) {
//      $params = array(
//        '%source' => $source,
//        '%target' => $target,
//        '%endpoint' => $xmlrpc_endpoint,
//      );
//      $methods = array(
//        'pingback.ping' => array($source, $target),
//      );
//      $result = xmlrpc($xmlrpc_endpoint, $methods, array('headers' => array('User-Agent' => self::UA)));
//      if ($result) {
//        $params = array(
//          '%source' => $source,
//          '%target' => $target,
//        );
//        return TRUE;
//      }
//      else {
//        $params = array(
//          '%source' => $source,
//          '%target' => $target,
//          '@errno' => xmlrpc_errno(),
//          '@description' => xmlrpc_error_msg(),
//        );
//        $this->logger->error('Pingback to %target from %source failed.<br />Error @errno: @description', $params);
//        return FALSE;
//      }
//
//    }
    // No XML-RPC endpoint detected; pingback failed.
    return FALSE;
  }
  /**
   * Get the URL of the XML-RPC endpoint that handles pingbacks for a URL.
   *
   * @param string $url
   *   URL of the remote article
   *
   * @return String|FALSE
   * Absolute URL of the XML-RPC endpoint, or FALSE if pingback is not
   * supported.
   */
//  protected function getXmlRpcEndpoint($url) {
//    try {
//      $response = $this->httpClient->get($url, array('headers' => array('Accept' => 'text/plain')));
//      $data = $response->getBody(TRUE);
//      $endpoint = $response->getHeader('X-Pingback');
//      if ($endpoint) {
//        return $endpoint[0];
//      }
//      $crawler = new Crawler((string) $data);
//      $endpoint = $crawler->filter('link[rel="pingback"]')->extract('href');
//      if ($endpoint) {
//        return $endpoint[0];
//      }
//    }
//    catch (BadResponseException $exception) {
//      $response = $exception->getResponse();
//      $this->logger->notice('Failed to fetch url %endpoint due to HTTP error "%error"', array(
//        '%endpoint' => $xmlrpc_endpoint,
//        '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
//      ));
//    }
//    catch (RequestException $exception) {
//      $this->logger->notice('Failed to fetch url %endpoint due to error "%error"', array(
//        '%endpoint' => $xmlrpc_endpoint,
//        '%error' => $exception->getMessage(),
//      ));
//    }
//    catch (InvalidArgumentException $exception) {
//      $this->logger->notice('Failed to fetch url %endpoint due to error "%error"', array(
//        '%endpoint' => $xmlrpc_endpoint,
//        '%error' => $exception->getMessage(),
//      ));
//    }
//    return FALSE;
//  }

}

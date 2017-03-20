<?php

namespace Drupal\linkback_webmention\EventSubscriber;

use IndieWeb\MentionClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\linkback_webmention\Event\LinkbackSendEvent;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use IndieWeb\MentionClient;

/**
 * Class WebmentionSendSubscriber.
 *
 * @package Drupal\linkback_webmention
 */
class WebmentionSendSubscriber implements EventSubscriberInterface {

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
   * A logger instance.
   *
   * @var \IndieWeb\MentionClient
   */
  protected $mentionClient;

  /**
   * Constructor.
   *
   * @param GuzzleHttp\Client $http_client
   *   GuzzleHttp\Client definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.   *    .*/
  public function __construct(Client $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->mentionClient = new MentionClient();
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
   * @param \Drupal\linkback_webmention\Event\LinkbackSendEvent $event
   *   The event to process.
   */
  public function onLinkbackSend(LinkbackSendEvent $event) {
    drupal_set_message('Event linkback_send thrown by Subscriber in module linkback_webmention.', 'status', TRUE);
    $this->sendWebmention($event->getSource(), $event->getTarget());
  }

  /**
   * Sends the pingback.
   *
   * @param \Drupal\Core\Url $sourceUrl
   *   The source url.
   * @param \Drupal\Core\Url $targetUrl
   *   The target url.
   *
   * @return array|bool
   *   False if error The response with:
   *     - code: the http return code.
   *     - headers: the return headers.
   *     - body: the returned body.
   *
   * @link https://github.com/indieweb/mention-client-php/blob/master/src/IndieWeb/MentionClient.php#L486
   */
  public function sendWebmention(Url $sourceUrl, Url $targetUrl) {
    $source = $sourceUrl->setOption("absolute", TRUE)->toString();
    $target = $targetUrl->setOption("absolute", TRUE)->toString();
    $supportsWebmention = $this->mentionClient->discoverWebmentionEndpoint($target);
    if ($supportsWebmention) {
      try {
        $response = $this->mentionClient->sendWebmention($source, $target);
        if ($response) {
          $this->logger->notice('Response: @resp', ['@resp' => $response]);
          return $response;
        }
      }
      catch (Exception $exception) {
        throw $exception;
        // TODO handle this exception to propagate correctly.
      }
    }
    return FALSE;

  }

}

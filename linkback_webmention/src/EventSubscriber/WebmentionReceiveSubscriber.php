<?php

namespace Drupal\linkback_webmention\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\linkback_webmention\LinkbackWebmentionParser;
use Drupal\linkback\Event\LinkbackReceiveEvent;
use Drupal\linkback\Exception\LinkbackException;
use Drupal\Core\Entity\EntityInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class WebmentionReceiveSubscriber.
 *
 * @package Drupal\linkback_webmention
 */
class WebmentionReceiveSubscriber implements EventSubscriberInterface {
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A WebMention Parser.
   *
   * @var \Drupal\linkback_webmention\LinkbackWebmentionParser
   */
  protected $webmentionParser;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\linkback_webmention\LinkbackWebmentionParser $parser
   *   GuzzleHttp\Client definition.
   */
  public function __construct(LoggerInterface $logger, LinkbackWebmentionParser $parser) {
    $this->logger = $logger;
    $this->webmentionParser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['linkback_receive'] = [['onLinkbackReceive', -10]];
    return $events;
  }

  /**
   * This method is called whenever the linkback_receive event is dispatched.
   *
   * @param \Drupal\linkback\Event\LinkbackReceiveEvent $event
   *   The event to process.
   */
  public function onLinkbackReceive(LinkbackReceiveEvent $event) {
    if ($event->getHandler() != "linkback_webmention") {
      return;
    }
    drupal_set_message('Event linkback_receive thrown by Subscriber in module linkback_webmention.', 'status', TRUE);
    $linkback = NULL;
    foreach ($event->getLinkbacks() as $existent_linkback) {
      $linkback = ($existent_linkback->get('handler')->getString() == "linkback_webmention") ? $existent_linkback : NULL;
    };
    $this->processWebmentionIntoLinkbackEntity($event->getSource(), $event->getTarget(), $event->getLocalEntity(), $event->getResponse(), $linkback);
  }

  /**
   * Receive the webmention.
   *
   * @param string $sourceUrl
   *   The source Url.
   * @param string $targetUrl
   *   The target Url.
   * @param \Drupal\Core\Entity\EntityInterface $local_entity
   *   The mentioned entity.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response fetched from source.
   * @param \Drupal\Core\Entity\EntityInterface|null $linkback
   *   The existent linkbacks with these source and target if any.
   */
  public function processWebmentionIntoLinkbackEntity($sourceUrl,
                                                      $targetUrl,
                                                      EntityInterface $local_entity,
                                                      ResponseInterface $response,
                                                      $linkback) {
    $urls = [
      '%source' => $sourceUrl,
      '%target' => $targetUrl,
    ];
    // STEPS: https://www.w3.org/TR/webmention/#request-verification
    // StEP 1: check if DELETED . If status 410 Gone  -> DELETE existent.
    if ($response->getStatusCode() == 410 && !empty($linkback)) {
      $this->logger->error('Received webmention from %source claims for deleting the mention of target: %target', $urls);
      $linkback->delete();
      return;
    }
    // Step 2: check if schema is http or https.
    if (!($this->webmentionParser->isValidSchema($sourceUrl, $targetUrl))) {
      $this->logger->error('Received webmention has not a valid schema source: %source target: %target', $urls);
      return;
    }
    // Step 3: check if source and target are not the same.
    if ($sourceUrl == $targetUrl) {
      $this->logger->error('Received webmention has same source: %source and target: %target', $urls);
      return;
    }
    // Step 4: check if $response body has target url.
    $body = (string) $response->getBody();
    if ($this->webmentionParser->hasLink($targetUrl, $body) === FALSE) {
      $this->logger->notice('Received webmention linkback source: %source hasn\'t link to target: %target ', $urls);
      return;
    };

    // Step 5: Get metainfo (h-card, foaf, simple ... ) First try mf2 then rdf,
    // finally: Basic.
    $metainfo = [];
    // Empty options array reserved for later.
    $options = [];
    if ($metainfo = $this->webmentionParser->getMf2Information($body, $sourceUrl)) {
      $this->logger->notice('Found relevant microformats in source: %source ', ['%source' => $sourceUrl]);
      $metainfo['parser'] = 'mf2';
    }
    elseif ($metainfo = $this->webmentionParser->getRdfInformation($body, $targetUrl)) {
      $this->logger->notice('Found relevant rdf in source: %source ', ['%source' => $sourceUrl]);
      $metainfo['parser'] = 'rdf';
    }
    elseif ($metainfo = $this->webmentionParser->getBasicMetainfo($body, $targetUrl)) {
      $this->logger->notice('Found relevant basic information in source: %source ', ['%source' => $sourceUrl]);
    }
    // At this point we could add basic information fetcher to override the
    // one that will provided the linkback entity presave method,
    // save using raw linkback functionality:
    $this->saveLinkbackEntity($sourceUrl, $targetUrl, $local_entity, $linkback, $metainfo, $options);

    if (empty($metainfo)) {
      $this->logger->error('Could not find relevant metainformation in origin @url', ['@url' => $sourceUrl]);
    }
  }

  /**
   * Saves the processed Webmention to linkback storage.
   *
   * @param string $source
   *   The source Url.
   * @param string $target
   *   The target Url.
   * @param \Drupal\Core\Entity\EntityInterface $local_entity
   *   The mentioned entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $linkback
   *   The existent linkbacks with these source and target if any.
   * @param array $metainfo
   *   The metainformation fetched from the source.
   * @param array $options
   *   Empty options array reserved for later.
   */
  protected function saveLinkbackEntity($source, $target, EntityInterface $local_entity, $linkback, array $metainfo, $options = []) {
    if (empty($linkback)) {
      $linkback = entity_create('linkback', [
        'handler'  => 'linkback_webmention',
        'ref_content' => $local_entity,
        'url'      => $source,
        'type'     => 'received',
      ]);
    }
    if (!empty($metainfo)) {
      $title = (isset($metainfo['name'])) ? $metainfo['name'] : "untitled";
      // As we must deal with rich and sometimes empty likes or favs fill
      // excerpt to deal with excerpt strict validation.
      $excerpt = (isset($metainfo['summary'])) ? $metainfo['summary'] : "No excerpt";
      $linkback->setTitle($title);
      $linkback->setExcerpt($excerpt);
      $serialized_metainfo = $this->serializeMetainfo($metainfo);
      $linkback->setMetainfo($serialized_metainfo);
    }

    try {
      $linkback->setChangedTime(time());
      $result = $linkback->save();
      $this->logger->notice(t('Webmention from @source to @target registered.', ['@source' => $source, '@target' => $target]));
    }
    catch (EntityStorageException $exception) {
      $this->logger->error(t('Webmention from @source to @target not registered.', ['@source' => $source, '@target' => $target]));
    }
    catch (LinkbackException $exception) {
      $this->logger->error(t('Webmention from @source to @target not registered due to error: %error.', [
        '@source' => $source,
        '@target' => $target,
        '%error' => $exception->getMessage(),
      ]));

    }
  }

  /**
   * Serializes metainfo.
   *
   * @param array $metainfo
   *   The parsed metainfo array.
   *
   * @return string
   *
   * @todo This could be split into different encoding options by user
   * configured choice.
   */
  protected function serializeMetainfo(array $metainfo) {
    $serialized_metainfo = "";
    return $serialized_metainfo .= json_encode($metainfo, JSON_PRETTY_PRINT);
  }

}

<?php

namespace Drupal\linkback_pingback\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\linkback\Event\LinkbackReceiveEvent;
use Drupal\Core\Entity\EntityInterface;
use Psr\Http\Message\ResponseInterface;
use Drupal\linkback\LinkbackService;

/**
 * Class WebmentionReceiveSubscriber.
 *
 * @package Drupal\linkback_webmention
 */
class PingbackReceiveSubscriber implements EventSubscriberInterface {
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Linkback service.
   *
   * @var \Drupal\linkback\LinkbackService
   */
  protected $linkbackService;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\linkback\LinkbackService $linkback_service
   *   The Linkback service.
   */
  public function __construct(LoggerInterface $logger, LinkbackService $linkback_service) {
    $this->logger = $logger;
    $this->linkbackService = $linkback_service;
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
    if ($event->getHandler() != "linkback_pingback") {
      return;
    }
    drupal_set_message('Event linkback_receive thrown by Subscriber in module linkback_pingback.', 'status', TRUE);
    $linkback = NULL;
    foreach ($event->getLinkbacks() as $existing_linkback) {
      $linkback = ($existing_linkback->get('handler')->getString() == "linkback_pingback") ? $existing_linkback : NULL;
    }
    $this->processPingback($event->getSource(), $event->getTarget(), $event->getLocalEntity(), $event->getResponse(), $linkback);
  }

  /**
   * Receive the webmention.
   *
   * @param string $source_url
   *   The source Url.
   * @param string $target_url
   *   The target Url.
   * @param \Drupal\Core\Entity\EntityInterface $local_entity
   *   The mentioned entity.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response fetched from source.
   * @param \Drupal\Core\Entity\EntityInterface|null $linkback
   *   The existant linkbacks with these source and target if any.
   */
  public function processPingback($source_url, $target_url, EntityInterface $local_entity, ResponseInterface $response, $linkback) {
    $urls = [
      '%source' => $source_url,
      '%target' => $target_url,
    ];
    // Step 2: check if schema is http or https.
    $source_valid_schema = substr($source_url, 0, 7) == "http://" || substr($source_url, 0, 8) == "https://";
    $target_valid_schema = substr($target_url, 0, 7) == "http://" || substr($target_url, 0, 8) == "https://";
    if (!($source_valid_schema && $target_valid_schema)) {
      $this->logger->error('Received pingback has not a valid schema source:%source target: %target', $urls);
      return;
    }
    // Step 3: check if source and target are not the same.
    if ($source_url == $target_url) {
      $this->logger->error('Received pingback has same source:%source and target: %target', $urls);
      return;
    }
    // Step 4: check if $response body has target url.
    $body = (string) $response->getBody();
    //TODO refactor this getTitleExcerpt when Service has stable methods.
    $title_excerpt = $this->linkbackService->getTitleExcerpt($local_entity->id(), $body);
    if (!$title_excerpt) {
      return;
    };

    // Step 5: At this point let's save linkback.
    $this->saveLinkback($source_url, $target_url, $local_entity, $linkback, $title_excerpt);

  }

  /**
   * Saves the processed webmention to linkback storage.
   *
   * @param string $source
   *   The source Url.
   * @param string $target
   *   The target Url.
   * @param \Drupal\Core\Entity\EntityInterface $local_entity
   *   The mentioned entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $linkback
   *   The existant linkbacks with these source and target if any.
   * @param array $title_excerpt
   *   The title and the excerpt.
   */
  protected function saveLinkback($source, $target, EntityInterface $local_entity, $linkback, array $title_excerpt) {
    if (empty($linkback)) {
      $linkback = entity_create('linkback', [
        'handler'  => 'linkback_pingback',
        'ref_content' => $local_entity,
        'url'      => $source,
        'type'     => 'received',
      ]);
    }
    if (!empty($title_excerpt)) {
      $linkback->setTitle($title_excerpt[0]);
      $linkback->setExcerpt($title_excerpt[1]);
    }

    try {
      $linkback->setChangedTime(time());
      $result = $linkback->save();
      $this->logger->notice(t('Pingback from @source to @target registered.', ['@source' => $source, '@target' => $target]));
    }
    catch (EntityStorageException $exception) {
      $this->logger->error(t('Pingback from @source to @target not registered.', ['@source' => $source, '@target' => $target]));
    }
  }

}

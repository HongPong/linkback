<?php

namespace Drupal\linkback\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;

/**
 * Event that is fired when a linkback needs to be send (rules event).
 *
 * @see rules_linkback_send()
 */
class LinkbackSendRulesEvent extends Event {

  const EVENT_NAME = 'rules_linkback_send';

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $source;

  /**
   * The target Url.
   *
   * @var \Drupal\Core\Url
   */
  protected $target;

  /**
   * Getter for the source entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The source entity.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Setter for the source entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   *   The source entity.
   */
  public function setSource(ContentEntityInterface $source) {
    $this->source = $source;
  }

  /**
   * Getter for the target Url.
   *
   * @return \Drupal\Core\Url
   *   The target Url.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Setter for the target Url.
   *
   * @param \Drupal\Core\Url $url
   *   The target Url.
   */
  public function setTargetUrl(Url $url) {
    $this->target = $url;
  }

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   *   The source Url.
   * @param string $target
   *   The target Url.
   */
  public function __construct(ContentEntityInterface $source, $target) {
    $this->source = $source;
    $this->target = $target;
  }

}

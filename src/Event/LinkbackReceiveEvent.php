<?php

namespace Drupal\linkback\Event;

use Symfony\Component\EventDispatcher\Event;
use Psr\Http\Message\ResponseInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event that is fired when a linkback is received.
 *
 * @see linkback_receive()
 */
class LinkbackReceiveEvent extends Event {

  const EVENT_NAME = 'linkback_receive';

  /**
   * The linkback handler that creates this item.
   *
   * @var string
   */
  protected $handler;

  /**
   * The source url.
   *
   * @var string
   */
  protected $source;

  /**
   * The target url.
   *
   * @var string
   */
  protected $target;

  /**
   * The mentioned entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $localEntity;

  /**
   * The response from source url.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * The existant linkbacks with these source and target if any.
   *
   * @var array
   */
  protected $linkbacks;

  /**
   * Constructs the object.
   *
   * @param string $handler
   *   The linkback handler that creates this event.
   * @param string $source
   *   The source Url.
   * @param string $target
   *   The target Url.
   * @param \Drupal\Core\Entity\EntityInterface $local_entity
   *   The mentioned entity.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response fetched from source.
   * @param array $linkbacks
   *   The existant linkbacks with these source and target if any.
   */
  public function __construct($handler, $source, $target, EntityInterface $local_entity, ResponseInterface $response, array $linkbacks) {
    $this->handler = $handler;
    $this->source = $source;
    $this->target = $target;
    $this->localEntity = $local_entity;
    $this->response = $response;
    $this->linkbacks = $linkbacks;
  }

  /**
   * Getter for the linkback handler.
   *
   * @return string
   *   The linkback handler.
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * Setter for the linkback handler.
   *
   * @param string $handler
   *   The linkback handler.
   */
  public function setHandler($handler) {
    $this->handler = $handler;
  }

  /**
   * Getter for the source Url.
   *
   * @return string
   *   The source Url.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Setter for the source Url.
   *
   * @param string $source
   *   The source Url.
   */
  public function setSource($source) {
    $this->source = $source;
  }

  /**
   * Getter for the target Url.
   *
   * @return string
   *   The target Url.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Setter for the target Url.
   *
   * @param string $target
   *   The target Url.
   */
  public function setTarget($target) {
    $this->target = $target;
  }

  /**
   * Getter for the local entity.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   The mentioned entity.
   */
  public function getLocalEntity() {
    return $this->localEntity;
  }

  /**
   * Setter for the local entity.
   *
   * @param Drupal\Core\Entity\EntityInterface $localEntity
   *   The mentioned entity.
   */
  public function setLocalEntity(EntityInterface $localEntity) {
    $this->localEntity = $localEntity;
  }

  /**
   * Getter for the response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response fetched from source.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Setter for the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response fetched from source.
   */
  public function setResponse(ResponseInterface $response) {
    $this->response = $response;
  }

  /**
   * Getter for the response.
   *
   * @return array
   *   The array of existant linkbacks if any.
   */
  public function getLinkbacks() {
    return $this->linkbacks;
  }

  /**
   * Setter for the linkbacks array.
   *
   * @param array $linkbacks
   *   The existant linkbacks with these source and target if any.
   */
  public function setLinkbacks(array $linkbacks) {
    $this->linkbacks = $linkbacks;
  }

}

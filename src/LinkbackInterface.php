<?php

namespace Drupal\linkback;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Linkback entities.
 *
 * @ingroup linkback
 */
interface LinkbackInterface extends ContentEntityInterface, EntityChangedInterface {

  const RECEIVED = 'received';

  const SENT = 'sent';

  /**
   * Gets the Linkback title.
   *
   * @return string
   *   Title of the Linkback.
   */
  public function getTitle();

  /**
   * Sets the Linkback title.
   *
   * @param string $title
   *   The Linkback title.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setTitle($title);

  /**
   * Gets the Linkback excerpt.
   *
   * @return string
   *   Excerpt of the Linkback.
   */
  public function getExcerpt();

  /**
   * Sets the Linkback excerpt.
   *
   * @param string $excerpt
   *   The Linkback excerpt.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setExcerpt($excerpt);

  /**
   * Gets the Linkback metainfo.
   *
   * @return string
   *   Metainfo of the Linkback.
   */
  public function getMetainfo();

  /**
   * Sets the Linkback metainfo.
   *
   * @param string $metainfo
   *   The Linkback metainfo.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setMetainfo($metainfo);

  /**
   * Gets the Linkback origin.
   *
   * @return string
   *   Origin of the Linkback.
   */
  public function getOrigin();

  /**
   * Sets the Linkback origin.
   *
   * @param string $origin
   *   The Linkback origin.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setOrigin($origin);

  /**
   * Gets the Linkback ref_content.
   *
   * @return string
   *   Referenced content of the Linkback.
   */
  public function getRefContent();

  /**
   * Sets the Linkback ref_content.
   *
   * @param string $ref_content
   *   The Linkback referenced content.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setRefContent($ref_content);

  /**
   * Gets the Linkback url.
   *
   * @return string
   *   Url of the Linkback.
   */
  public function getUrl();

  /**
   * Sets the Linkback url.
   *
   * @param string $url
   *   The Linkback Url.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setUrl($url);

  /**
   * Gets the Linkback handler.
   *
   * @return string
   *   Handler of the Linkback.
   */
  public function getHandler();

  /**
   * Sets the Linkback handler.
   *
   * @param string $handler
   *   The Linkback handler.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setHandler($handler);

  /**
   * Gets the Linkback creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Linkback.
   */
  public function getCreatedTime();

  /**
   * Sets the Linkback creation timestamp.
   *
   * @param int $timestamp
   *   The Linkback creation timestamp.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Linkback published status indicator.
   *
   * Unpublished Linkback are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Linkback is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Linkback.
   *
   * @param bool $published
   *   TRUE to set this Linkback to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\linkback\LinkbackInterface
   *   The called Linkback entity.
   */
  public function setPublished($published);

}

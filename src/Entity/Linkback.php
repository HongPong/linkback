<?php

namespace Drupal\linkback\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\linkback\LinkbackInterface;

/**
 * Defines the Linkback entity.
 *
 * @ingroup linkback
 *
 * @ContentEntityType(
 *   id = "linkback",
 *   label = @Translation("Linkback"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\linkback\LinkbackListBuilder",
 *     "views_data" = "Drupal\linkback\Entity\LinkbackViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\linkback\Form\LinkbackForm",
 *       "add" = "Drupal\linkback\Form\LinkbackForm",
 *       "edit" = "Drupal\linkback\Form\LinkbackForm",
 *       "delete" = "Drupal\linkback\Form\LinkbackDeleteForm",
 *     },
 *     "access" = "Drupal\linkback\LinkbackAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\linkback\LinkbackHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "linkback",
 *   admin_permission = "administer linkback entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/linkback/{linkback}",
 *     "add-form" = "/admin/content/linkback/add",
 *     "edit-form" = "/admin/content/linkback/{linkback}/edit",
 *     "delete-form" = "/admin/content/linkback/{linkback}/delete",
 *   }
 * )
 */
class Linkback extends ContentEntityBase implements LinkbackInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'status' => 1,
      'type' => 'received',
      'created' => time(),
      'updated' => time(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcerpt() {
    return $this->get('excerpt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExcerpt($excerpt) {
    $this->set('excerpt', $excerpt);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrigin() {
    return $this->get('origin')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrigin($origin) {
    $this->set('origin', $origin);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefContent() {
    return $this->get('ref_content')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefContent($ref_content) {
    $this->get('ref_content')->target_id = $ref_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->get('url')->value = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler() {
    return $this->get('handler')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandler($handler) {
    $this->set('handler', $handler);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Linkback entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Linkback entity.'))
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the node is published.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Linkback entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the Linkback entity.'));

    $fields['ref_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content reference'))
      ->setDescription(t('The content id.'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    $fields['url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL'))
      ->setDescription(t('The fully-qualified URL of the remote url.'))
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Linkback entity.'))
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['excerpt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Excerpt'))
      ->setDescription(t("Excerpt of the third-party's post."))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['handler'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handler'))
      ->setDescription(t("The handler for this linkback."))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['origin'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Origin'))
      ->setDescription(t('Identifier of the origin, such as an IP address or hostname.'))
      ->setDefaultValue(0)
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}

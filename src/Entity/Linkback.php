<?php

namespace Drupal\linkback\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\linkback\LinkbackInterface;
use Drupal\field\Entity\FieldConfig;

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
 *   fieldable = TRUE,
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
 *   },
 *   field_ui_base_route = "entity.linkback_type.edit_form"
 * )
 */
class Linkback extends ContentEntityBase implements LinkbackInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'status' => 1,
      'type' => 'received',
      'created' => time(),
      'updated' => time(),
    ];
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
  public function getMetainfo() {
    return $this->get('metainfo')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMetainfo($metainfo) {
    $this->set('metainfo', $metainfo);
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
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
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
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'link',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Linkback entity.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['excerpt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Excerpt'))
      ->setDescription(t("Excerpt of the third-party's post."))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['metainfo'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metainfo'))
      ->setDescription(t("Metainfo of the third-party's post."))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['handler'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handler'))
      ->setDescription(t("The handler for this linkback."))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['origin'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Origin'))
      ->setDescription(t('Identifier of the origin, such as an IP address or hostname.'))
      ->setDefaultValue(0)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
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

  /**
   * Save a received ref-back.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Invoke validation.
    $this->setValidationRequired(TRUE);
    if ($this->validate()->count() == 0) {
      // Save the entity.
      // Entity presave/update/insert hooks will be invoked by the entity API
      // controller.
      // @see hook_linkback_received_presave()
      // @see hook_linkback_received_insert()
      // @see hook_linkback_received_update()
      \Drupal::logger('linkback')->notice("Tempted linkback could be registered");
    }
    else {
      if ($this->validate()->getByFields(['handler'])->count() == 1) {
        \Drupal::logger('linkback')->error("The refback-handler must be provided.");
        throw new LinkbackException('The refback-handler must be provided.');
      }
      if ($this->validate()->getByFields(['title'])->count() == 1 || $this->validate()->getByFields(['excerpt'])->count() == 1) {
        try {
          $local_url = \Drupal::service('linkback.default')->getLocalUrl($this->getRefContent());
          $data = \Drupal::service('linkback.default')->getRemoteData($this->getRefContent(), $this->getUrl(), $local_url);
          list($title, $excerpt) = $data;
          if (empty($this->getTitle())) {
            $this->setTitle($title);
          }
          if (empty($this->getExcerpt())) {
            $this->setExcerpt($excerpt);
          }

        }
        catch (Exception $exception) {
          throw new LinkbackException($exception->getMessage(), $exception->getCode());
        }
      }
      if ($this->validate()->getEntityViolations()->count() > 0) {
        // COND FOR LINKBACK_ERROR_REFBACK_ALREADY_REGISTERED
        // AND COND FOR LINKBACK_ERROR_LOCAL_NODE_REFBACK_NOT_ALLOWED.
        $violation = $this->validate()->getEntityViolations()[0];
        throw new LinkbackException($violation->getCause(), $violation->getCode());
      }

    }
    $this->setOrigin(\Drupal::request()->getClientIP());

    if (!empty($this->getMetainfo())) {
      // If json in getMetainfo fill the mapped values in custom fields.
      $metainfo = json_decode($this->getMetainfo(), TRUE);
      $parser = $metainfo['parser'];
      $fields_config = array_filter($this->fieldDefinitions, function ($element) {
        return $element instanceof FieldConfig;
      });
      foreach ($fields_config as $field_config) {
        $this->setFieldFromMetainfo($field_config, $metainfo, $parser);
      }
    }
  }

  /**
   * Set field based on the values of metainfo field and mapping setting.
   *
   * @param \Drupal\field\Entity\FieldConfig $definition
   *   The definition of the field.
   * @param array $metainfo
   *   The metainfo coming from json string stored in metainfo field.
   * @param string $parser
   *   The parser description name.
   */
  protected function setFieldFromMetainfo(FieldConfig $definition, array $metainfo, $parser) {
    $property_path = $definition->getThirdPartySetting('linkback', $parser . '_mapping');
    if (!$property_path) {
      return;
    }
    // Get the  value specified in mapping field.
    $properties = explode("/", $property_path);
    $pathfinder = $metainfo;
    foreach ($properties as $property) {
      $pathfinder = isset($pathfinder[$property]) ? $pathfinder[$property] : NULL;
    }

    if (is_string($pathfinder)) {
      $field_name = $definition->get('field_name');
      $allowed_types = [
        'string',
        'string_long',
        'text',
        'text_long',
        'text_with_summary',
        'link',
      ];
      if (in_array($definition->getType(), $allowed_types)) {
        $this->set($field_name, $pathfinder);
      }
    }
  }

}

<?php

namespace Drupal\linkback\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'linkback_handlers' field type.
 *
 * @FieldType(
 *   id = "linkback_handlers",
 *   label = @Translation("Linkback handlers"),
 *   description = @Translation("This field stores linkback enabled handlers"),
 *   default_widget = "linkback_default_widget",
 *   default_formatter = "linkback_formatter"
 * )
 */
class LinkbackHandlerItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['linkback_receive'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Receive linkbacks'));
    $properties['linkback_send'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Send linkbacks'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'linkback_receive' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'linkback_send' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('linkback_send')->getValue();
    return $value === NULL || $value === '';
  }

}

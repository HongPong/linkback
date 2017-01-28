<?php

/**
 * @file
 */

namespace Drupal\linkback;

use Drupal\views\EntityViewsData;

/**
 * LinkbackViewsData Class.
 */
class LinkbackViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    // Start with the Views information provided by the base class.
    $data = parent::getViewsData();

    // Override a few things...
    // Define a wizard.
    $data['linkback_field_data']['table']['wizard_id'] = 'linkback';

    // You could also override labels or put in a custom field
    // or filter handler.
    return $data;
  }

}

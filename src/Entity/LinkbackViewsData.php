<?php

namespace Drupal\linkback\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Linkback entities.
 */
class LinkbackViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['linkback']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Linkback'),
      'help' => $this->t('The Linkback ID.'),
    ];
    return $data;
  }

}

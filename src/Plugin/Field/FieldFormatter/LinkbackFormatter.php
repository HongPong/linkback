<?php

namespace Drupal\linkback\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'linkback_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "linkback_formatter",
 *   label = @Translation("Linkback formatter"),
 *   field_types = {
 *     "linkback_handlers"
 *   }
 * )
 */
class LinkbackFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */

  /**
   * TODO ALLOW OTHER LINKBACKS HANDLERS.
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // TODO CALL MODULE_IMPLEMENT INVOKE_ALL HOOKS!
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $send = ($item->linkback_send) ? $this->t('Send linkbacks option is enabled') : $this->t('Send linkbacks option is disabled');
    $receive = ($item->linkback_receive) ? $this->t('Receive linkbacks option is enabled') : $this->t('Receive linkbacks option is disabled');
    return $send . "<br/>" . $receive;
  }

}

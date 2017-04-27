<?php

namespace Drupal\linkback\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkback_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "linkback_default_widget",
 *   label = @Translation("Linkback default widget"),
 *   field_types = {
 *     "linkback_handlers"
 *   }
 * )
 */
class LinkbackDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      $plugin_id,
      $plugin_definition,
      FieldDefinitionInterface $field_definition,
      array $settings,
      AccountInterface $current_user
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      []
    );
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * TODO allow extra linkbacks methods here.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * TODO allow extra linkbacks methods here.
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    $elements['linkback_send'] = [
      '#type' => 'checkbox',
      '#title' => t('Send linkbacks'),
      '#default_value' => isset($items->linkback_send) ? $items->linkback_send : TRUE,
      '#access' => $this->currentUser->hasPermission('toggle linkback send option on content'),
    ];
    $elements['linkback_receive'] = [
      '#type' => 'checkbox',
      '#title' => t('Receive linkbacks'),
      '#default_value' => isset($items->linkback_receive) ? $items->linkback_receive : TRUE,
      '#access' => $this->currentUser->hasPermission('toggle linkback receive option on content'),
    ];
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $elements += [
        '#title' => t('Linkbacks'),
        '#type' => 'details',
        '#group' => 'advanced',
      ];
    }

    return $elements;
  }

}

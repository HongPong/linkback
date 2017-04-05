<?php

namespace Drupal\linkback_webmention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LinkbackWebmentionsSettingsForm.
 *
 * @package Drupal\linkback\Form
 */
class WebmentionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'linkback_webmention.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_webmention_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkback_webmention.settings');
    $helpstring = "Webmentions are a more modern form of trackbacks. You can control the settings here. "
        . "Use the Webmentions Tests tab to see if you can scrape remotely. "
        . "Endpoints must be enabled for Webmentions to be received. "
        . "See <a href='http://cgit.drupalcode.org/linkback/tree/linkback_webmention/README.md?h=8.x-1.x'>"
        . "linkback_webmention README.md</a> for further information.";
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t($helpstring),
    ];

    $form['endpoints_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Webmention endpoints'),
      '#description' => $this->t('Webmentions endpoints enabled?') ,
      '#default_value' => $config->get('endpoints_enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config = $this->config('linkback_webmention.settings');
    // TODO CHECK IF IT CAN BE CHANGED (no items in queue!!!);
    // TODO provide link to process queue.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('linkback_webmention.settings')
      ->set('endpoints_enabled', $form_state->getValue('endpoints_enabled'))
      ->save();
  }

}

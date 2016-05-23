<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
/**
 * Class LinkbackSettingsForm.
 *
 * @package Drupal\linkback\Form
 */
class LinkbackSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'linkback.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkback.settings');
    $form['use_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use cron'),
      '#description' => $this->t('Use cron to process the sending of linkbacks.') ,
      '#default_value' => $config->get('use_cron'),
    ];

     
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('linkback.settings');
    parent::validateForm($form, $form_state);
    //TODO CHECK IF IT CAN BE CHANGED (no items in queue!!!);(provide link to process queue.
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get($config->get('use_cron') ? 'cron_linkback_sender' : 'manual_linkback_sender' );
    if ($queue->numberOfItems() > 0){
      $form_state->setErrorByName('use_cron', t('Could not change this options as @qitems items remain in queue, run or remove these in queue tab', array('@qitems' => $queue->numberOfItems())) );
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('linkback.settings')
      ->set('use_cron', $form_state->getValue('use_cron'))
      ->save();
  }

}

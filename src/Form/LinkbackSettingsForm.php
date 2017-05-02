<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $form['use_cron_send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use cron for sending linkbacks'),
      '#description' => $this->t('Use cron to process the sending of linkbacks.') ,
      '#default_value' => $config->get('use_cron_send'),
    ];
    $form['use_cron_received'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use cron for received linkbacks'),
      '#description' => $this->t('Check received linkbacks with cron.') ,
      '#default_value' => $config->get('use_cron_received'),
    ];
    // @todo add fetch counter field https://www.drupal.org/node/2874748
    // @todo add length of excerpt field https://www.drupal.org/node/2874801
    // @todo add size limit of fetched data https://www.drupal.org/node/2874801
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config = $this->config('linkback.settings');
    // TODO CHECK IF IT CAN BE CHANGED (no items in queue!!!);
    // TODO provide link to process queue.
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue_send = $queue_factory->get($config->get('use_cron_send') ? 'cron_linkback_sender' : 'manual_linkback_sender');
    if ($queue_send->numberOfItems() > 0) {
      $form_state->setErrorByName('use_cron_send', t('Could not change this options as @qitems items remain in sending queue, run or remove these in queue tab', ['@qitems' => $queue_send->numberOfItems()]));
    }
    $queue_received = $queue_factory->get($config->get('use_cron_received') ? 'cron_linkback_receiver' : 'manual_linkback_receiver');
    if ($queue_received->numberOfItems() > 0) {
      $form_state->setErrorByName('use_cron_received', t('Could not change this options as @qitems items remain in received queue, run or remove these in queue tab', ['@qitems' => $queue_received->numberOfItems()]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('linkback.settings')
      ->set('use_cron_send', $form_state->getValue('use_cron_send'))
      ->set('use_cron_received', $form_state->getValue('use_cron_received'))
      ->save();
  }

}

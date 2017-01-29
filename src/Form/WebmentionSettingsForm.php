<?php

/**
 * @file
 * Webmention settings form
 */

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
        $form['help'] = array(
            '#type' => 'markup',
            '#markup' => $this->t('Webmentions are a more modern form of trackbacks. You can control the settings here. Use the Webmentions Tests tab to see if you can scrape remotely.'),
        );

        $form['use_cron'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Use cron'),
            '#description' => $this->t('Webmentions endpoints enabled?') ,
            '#default_value' => $config->get('use_cron'),
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
        /** @var QueueFactory $queue_factory */
        $queue_factory = \Drupal::service('queue');
        /** @var QueueInterface $queue */
        $queue = $queue_factory->get($config->get('use_cron') ? 'cron_linkback_webmention_sender' : 'manual_linkback_webmention_sender');
        if ($queue->numberOfItems() > 0) {
            $form_state->setErrorByName('use_cron', t('Could not change this options as @qitems items remain in queue, run or remove these in queue tab', array('@qitems' => $queue->numberOfItems())));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

        $this->config('linkback_webmention.settings')
            ->set('use_cron', $form_state->getValue('use_cron'))
            ->save();
    }

}

<?php

namespace Drupal\linkback_webmention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkback_webmention\Webmention;

/**
 * The class for Linkback sender queue form. Based on FormBase.
 */
class WebmentionTestsForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webmention_tests_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Here are functions to test remote Webmentions. '),
    ];
    $form['actions']['#type'] = 'actions';

    $form['actions']['RemoteURL'] = [
      '#type' => 'url',
      '#title' => $this->t('Remote URL to scrape for Webmentions'),
      '#size' => 40,
    ];
    $form['actions']['debugmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#description' => $this->t('Set debug flag in mention-client library for third-party scrape.') ,
      '#return_value' => TRUE,
            // '#default_value' => $config->get('use_cron'),.
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test remote URL'),
      '#button_type' => 'primary',
      '#submit' => ['::testRemoteURL'],
        // '#disabled' => $queue->numberOfItems() < 1,.
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete all sent/received Webmentions'),
      '#button_type' => 'secondary',
      '#submit' => ['::deleteQueue'],
      '#description' => $this->t('Not implemented'),
      '#disabled' => TRUE,
    ];

    $form['incoming_test']['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Here are functions to test local Webmentions and save an entity correctly.'),
    ];
    $form['incoming_test']['checkRemoteContent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check remote URL for valid inbound link'),
      '#description' => $this->t('If true, it will go look for the URL remotely before saving. Not implemented'),
      '#disabled' => TRUE,
    ];
    $form['incoming_test']['localtargetURL'] = [
      '#type' => 'url',
      '#title' => $this->t('Local target URL (should be existing node URL)'),
      '#size' => 40,
    ];
    $form['incoming_test']['remoteSenderURL'] = [
      '#type' => 'url',
      '#title' => $this->t('Remote URL get webmention from'),
      '#size' => 40,
    ];
    $form['incoming_test']['triggerIncoming'] = [
      '#type' => 'submit',
      '#value' => $this->t('Trigger incoming Webmention save'),
      '#button_type' => 'secondary',
      '#submit' => ['::testLocalTarget'],
        // '#disabled' => $queue->numberOfItems() < 1,.
    ];
    return $form;
  }

  /**
   * Tries to check the remote URL using function at src/Webmention.php.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function testRemoteURL(array &$form, FormStateInterface $form_state) {
    $target = $form_state->getValue('RemoteURL');
    $debug = (bool) $form_state->getValue('debugmode');
    $resultmessage = 'Test: URL tested: ' . $target;
    \Drupal::logger('linkback_webmention')->notice($resultmessage);
    drupal_set_message($resultmessage, $type = 'status', $repeat = FALSE);
    $falsebool = Webmention::staticThing();
    $clientObj = new Webmention();
    $theresult = 'checkRemoteURL: ' . $clientObj->checkRemoteUrl($target, $debug);
    drupal_set_message($theresult, $type = 'status', $repeat = FALSE);
  }

  /**
   * Tries to save a local entity.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function testLocalTarget(array &$form, FormStateInterface $form_state) {
    $localTarget = $form_state->getValue('localtargetURL');
    $remoteSender = $form_state->getValue('remoteSenderURL');
    $debug = $form_state->getValue('debugmode');
    // Todo the logger string is not sanitized correctly at all.
    \Drupal::logger('linkback_webmention')->notice('Trying to do a local target test from ' . $remoteSender . ' to ' . $localTarget);
    // Todo return values should be made useful.
    $testval = linkback_webmention__receive_webmention($remoteSender, $localTarget);
    kint($testval);
    $testmsg = 'Webmention: Tested local target: ' . $testval;
    \Drupal::logger('linkback_webmention')->notice($testmsg);

    $this->config('linkback_webmention.settings')
      ->set('debugmode', $form_state->getValue('debugmode'))
      ->save();
  }

}

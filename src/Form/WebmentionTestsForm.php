<?php

/**
 * @file
 */

namespace Drupal\linkback_webmention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkback_webmention\Webmention;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use IndieWeb\MentionClient;

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
//    public function __construct(
////        QueueFactory $queue,
////        QueueWorkerManagerInterface $queue_manager,
////        ConfigFactoryInterface $config_factory
//    ) {
////        $this->queueFactory = $queue;
////        $this->queueManager = $queue_manager;
////        $this->configFactory = $config_factory;
//    }

    /**
     * {@inheritdoc}
     */
//    public static function create(ContainerInterface $container) {
////        return new static(
////            $container->get('queue'),
////            $container->get('plugin.manager.queue_worker'),
////            $container->get('config.factory')
////        );
//    }

    /**
     * Gets the cron or manual queue.
     *
     * @return string
     *   The name of the QueueFactory.
     */
    protected function getQueue() {
        //$config = $this->configFactory->get('linkback_webmention.settings');
        //return $config->get('use_cron') ? 'cron_linkback_sender' : 'manual_linkback_sender';
    }

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
        //$queue = $this->queueFactory->get($this->getQueue());

        $form['help'] = array(
            '#type' => 'markup',
            '#markup' => $this->t('Here are functions to test remote Webmentions'),
        );
        $form['actions']['#type'] = 'actions';

        $form['actions']['RemoteURL'] = array(
            '#type' => 'url',
            '#title' => $this->t('Remote URL to scrape for Webmentions'),
            '#size' => 40,
        );
        $form['actions']['debugmode'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Debug mode'),
            '#description' => $this->t('Use debug flag on testing Webmention scrape .') ,
            //'#default_value' => $config->get('use_cron'),
        ];
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Test remote URL'),
            '#button_type' => 'primary',
            '#submit' => array('::testRemoteURL'),
            //'#disabled' => $queue->numberOfItems() < 1,
        );
        $form['actions']['delete'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Delete all sent/received Webmentions'),
            '#button_type' => 'secondary',
            '#submit' => array('::deleteQueue'),
            '#disabled' => true,
        );

        $form['incoming_test']['help'] = array(
            '#type' => 'markup',
            '#markup' => $this->t('Here are functions to test local Webmentions and save an entity correctly.'),
        );
        $form['incoming_test']['checkRemoteContent'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Check remote URL for valid inbound link'),
            '#description' => $this->t('If true, it will go look for the URL remotely before saving. Not implemented'),
            '#disabled' => true,
        ];
        $form['incoming_test']['localtargetURL'] = array(
            '#type' => 'url',
            '#title' => $this->t('Local target URL (should be existing node URL)'),
            '#size' => 40,
        );
        $form['incoming_test']['remoteSenderURL'] = array(
            '#type' => 'url',
            '#title' => $this->t('Remote URL get webmention from'),
            '#size' => 40,
        );
        $form['incoming_test']['triggerIncoming'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Trigger incoming Webmention save'),
            '#button_type' => 'secondary',
            '#submit' => array('::testLocalTarget'),
            //'#disabled' => $queue->numberOfItems() < 1,
        );
        return $form;
    }

    /**
     * Tries to check the remote URL using function at src/Webmention.php
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function testRemoteURL(array &$form, FormStateInterface $form_state) {
        /** @var \Drupal\Core\Queue\QueueInterface $queue */
        //$queue = $this->queueFactory->get($this->getQueue());
        //$queue->deleteQueue();
        $target = $form_state->getValue('RemoteURL');
        $debug = $form_state->getValue('debugmode');
        $resultmessage = 'Test: URL tested: ' . $target;
        \Drupal::logger('linkback_webmention')->notice($resultmessage);
        drupal_set_message($resultmessage, $type = 'status', $repeat = FALSE);
        //kint($form_state);

        Webmention::checkRemoteURL($target, $debug);
    }

    /**
     * Tries to save a local entity.
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function testLocalTarget(array &$form, FormStateInterface $form_state) {
        $localTarget = $form_state->getValue('localtargetURL');
        $remoteSender = $form_state->getValue('remoteSenderURL');
        $debug = $form_state->getValue('debugmode');
        // todo the logger string is not sanitized correctly at all.
        \Drupal::logger('linkback_webmention')->notice('Trying to do a local target test from '. $remoteSender . ' to ' . $localTarget);
        // todo return values should be made useful.
        $testval = linkback_webmention__receive_webmention($remoteSender, $localTarget);
        kint($testval);
        $testmsg = 'Webmention: Tested local target: ' . $testval ;
        \Drupal::logger('linkback_webmention')->notice($testmsg);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        /** @var \Drupal\Core\Queue\QueueInterface $queue */
//        $queue = $this->queueFactory->get($this->getQueue());
//        /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
//        $queue_worker = $this->queueManager->createInstance($this->getQueue());
//
//        while ($item = $queue->claimItem()) {
//            try {
//                $queue_worker->processItem($item->data);
//                $queue->deleteItem($item);
//            }
//            catch (SuspendQueueException $e) {
//                $queue->releaseItem($item);
//                break;
//            }
//            catch (\Exception $e) {
//                watchdog_exception('linkback', $e);
//            }
//        }
    }

}

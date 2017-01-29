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
            '#value' => $this->t('Delete the universe'),
            '#button_type' => 'secondary',
            '#submit' => array('::deleteQueue'),
            //'#disabled' => $queue->numberOfItems() < 1,
        );
        return $form;
    }


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

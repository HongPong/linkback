<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The class for Linkback sender queue form. Based on FormBase.
 */
class LinkbackSenderQueueForm extends FormBase {

  /**
   * The queue factory.
   *
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * The quqeue manager.
   *
   * @var QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      QueueFactory $queue,
      QueueWorkerManagerInterface $queue_manager,
      ConfigFactoryInterface $config_factory
  ) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('config.factory')
    );
  }

  /**
   * Gets the cron or manual queue.
   *
   * @return string
   *   The name of the QueueFactory.
   */
  protected function getQueue() {
    $config = $this->configFactory->get('linkback.settings');
    return $config->get('use_cron_send') ? 'cron_linkback_sender' : 'manual_linkback_sender';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_sender_queue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());

    $form['help'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('Submitting this form will process the "@queue" queue which contains @number items.', array('@queue' => $this->getQueue(), '@number' => $queue->numberOfItems())),
    );
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Process queue'),
      '#button_type' => 'primary',
      '#disabled' => $queue->numberOfItems() < 1,
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete queue'),
      '#button_type' => 'secondary',
      '#submit' => array('::deleteQueue'),
      '#disabled' => $queue->numberOfItems() < 1,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());
    $queue->deleteQueue();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get($this->getQueue());
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = $this->queueManager->createInstance($this->getQueue());

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        watchdog_exception('linkback', $e);
      }
    }
  }

}

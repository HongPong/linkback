<?php

namespace Drupal\linkback\Form;

use Drupal\linkback\LinkbackInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the linkbacks overview administration form.
 */
class LinkbackAdminOverview extends FormBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The linkback storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkbackStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a CommentAdminOverview form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkback_storage
   *   The linkback storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
      EntityManagerInterface $entity_manager,
      EntityStorageInterface $linkback_storage,
      DateFormatterInterface $date_formatter,
      ModuleHandlerInterface $module_handler
  ) {
    $this->entityManager = $entity_manager;
    $this->linkbackStorage = $linkback_storage;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.manager')->getStorage('linkback'),
      $container->get('date.formatter'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_admin_overview';
  }

  /**
   * Form constructor for the linkback overview administration form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   The type of the overview form ('local' or 'remote').
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'local') {

    // Build an 'Update options' form.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => array(
        'class' => array(
          'container-inline',
        ),
      ),
    );

    $options['publish'] = $this->t('Publish the selected linkbacks');
    $options['unpublish'] = $this->t('Unpublish the selected linkbacks');

    $form['options']['operation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    );
    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    );

    $header = array(
      'title' => array(
        'data' => $this->t('Title'),
        'specifier' => 'title',
      ),
      'excerpt' => array(
        'data' => $this->t('Excerpt'),
        'specifier' => 'excerpt',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'origin' => array(
        'data' => $this->t('Origin'),
        'specifier' => 'excerpt',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'handler' => array(
        'data' => $this->t('Handler'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'ref_content' => array(
        'data' => $this->t('Local content'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'url' => array(
        'data' => $this->t('Remote content'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'changed' => array(
        'data' => $this->t('Changed date'),
        'specifier' => 'created',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'operations' => $this->t('Operations'),
    );

    $type = ($type == 'received') ? LinkbackInterface::RECEIVED : LinkbackInterface::SENT;
    $lids = $this->linkbackStorage->getQuery()
      ->condition('type', $type)
      ->tableSort($header)
      ->pager(50)
      ->execute();

    /* @var $linkbacks \Drupal\linkback\LinkbackInterface[] */
    $linkbacks = $this->linkbackStorage->loadMultiple($lids);

    // Build a table listing the appropriate linkbacks.
    $options = array();
    $destination = $this->getDestinationArray();
    foreach ($linkbacks as $linkback) {
      /* @var $linkback \Drupal\Core\Entity\EntityInterface */
      $options[$linkback->id()] = array(
        'title' => $linkback->getTitle(),
        'excerpt' => $linkback->getExcerpt(),
        'origin' => $linkback->getOrigin(),
        'handler' => $linkback->getHandler(),
        'ref_content' => ['data' => $linkback->get('ref_content')->view()[0]],
        'url' => ['data' => $linkback->get('url')->view()[0]],
        'changed' => $this->dateFormatter->format(
          $linkback->getChangedTime(),
          'short'
        ),
      );

      $linkback_uri_options = $linkback->urlInfo()->getOptions() + ['query' => $destination];
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => $linkback->urlInfo('edit-form', $linkback_uri_options),
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => $linkback->urlInfo('delete-form', $linkback_uri_options),
      );
      $options[$linkback->id()]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    $form['linkbacks'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No linkbacks available.'),
    );

    $form['pager'] = array('#type' => 'pager');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('linkbacks', array_diff($form_state->getValue('linkbacks'), array(0)));
    // We can't execute any 'Update options' if no linkbacks were selected.
    if (count($form_state->getValue('linkbacks')) == 0) {
      $form_state->setErrorByName('', $this->t('Select one or more linkbacks to perform the update on.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $lids = $form_state->getValue('linkbacks');

    foreach ($lids as $lid) {
      // Delete operation handled in \Drupal\linkback\Form\ConfirmDeleteMultiple
      // see \Drupal\linkback\Controller\AdminController::adminPage().
      if ($operation == 'unpublish') {
        $linkback = $this->linkbackStorage->load($lid);
        $linkback->setPublished(FALSE);
        $linkback->save();
      }
      elseif ($operation == 'publish') {
        $linkback = $this->linkbackStorage->load($lid);
        $linkback->setPublished(TRUE);
        $linkback->save();
      }
    }
    drupal_set_message($this->t('The update has been performed.'));
    $form_state->setRedirect('linkback.admin');
  }

}

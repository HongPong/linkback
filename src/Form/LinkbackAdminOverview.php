<?php

namespace Drupal\linkback\Form;

use Drupal\linkback\LinkbackInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkback_storage
   *   The linkback storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
      EntityTypeManagerInterface $entity_type_manager,
      EntityStorageInterface $linkback_storage,
      DateFormatterInterface $date_formatter,
      ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->linkbackStorage = $linkback_storage;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage('linkback'),
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
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],
    ];

    $options['publish'] = $this->t('Publish the selected linkbacks');
    $options['unpublish'] = $this->t('Unpublish the selected linkbacks');

    $form['options']['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    ];
    $form['options']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    $header = [
      'title' => [
        'data' => $this->t('Title'),
        'specifier' => 'title',
      ],
      'excerpt' => [
        'data' => $this->t('Excerpt'),
        'specifier' => 'excerpt',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'origin' => [
        'data' => $this->t('Origin'),
        'specifier' => 'excerpt',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'handler' => [
        'data' => $this->t('Handler'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'ref_content' => [
        'data' => $this->t('Local content'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'url' => [
        'data' => $this->t('Remote content'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'changed' => [
        'data' => $this->t('Changed date'),
        'specifier' => 'created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'operations' => $this->t('Operations'),
    ];

    $type = ($type == 'received') ? LinkbackInterface::RECEIVED : LinkbackInterface::SENT;
    $lids = $this->linkbackStorage->getQuery()
      ->condition('type', $type)
      ->tableSort($header)
      ->pager(50)
      ->execute();

    /* @var $linkbacks \Drupal\linkback\LinkbackInterface[] */
    $linkbacks = $this->linkbackStorage->loadMultiple($lids);

    // Build a table listing the appropriate linkbacks.
    $options = [];
    $destination = $this->getDestinationArray();
    foreach ($linkbacks as $linkback) {
      /* @var $linkback \Drupal\Core\Entity\EntityInterface */
      $options[$linkback->id()] = [
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
      ];

      $linkback_uri_options = $linkback->urlInfo()->getOptions() + ['query' => $destination];
      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $linkback->toUrl('edit-form', $linkback_uri_options),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $linkback->toUrl('delete-form', $linkback_uri_options),
      ];
      $options[$linkback->id()]['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    $form['linkbacks'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No linkbacks available.'),
    ];

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('linkbacks', array_diff($form_state->getValue('linkbacks'), [0]));
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

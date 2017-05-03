<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the linkback multiple delete confirmation form.
 */
class ConfirmDeleteMultiple extends ConfirmFormBase {

  /**
   * The linkback storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkbackStorage;

  /**
   * An array of linkbacks to be deleted.
   *
   * @var \Drupal\linkback\LinkbackInterface[]
   */
  protected $linkbacks;

  /**
   * Creates an new ConfirmDeleteMultiple form.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkback_storage
   *   The linkback storage.
   */
  public function __construct(EntityStorageInterface $linkback_storage) {
    $this->linkbackStorage = $linkback_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('linkback')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkback_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these linkbacks?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('linkback.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete linkbacks');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $edit = $form_state->getUserInput();

    $form['linkbacks'] = [
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE,
    ];
    // array_filter() returns only elements with actual values.
    $linkback_counter = 0;
    $this->linkbacks = $this->linkbackStorage->loadMultiple(array_keys(array_filter($edit['linkbacks'])));
    foreach ($this->linkbacks as $linkback) {
      $lid = $linkback->id();
      $form['linkbacks'][$lid] = [
        '#type' => 'hidden',
        '#value' => $lid,
        '#prefix' => '<li>',
        '#suffix' => Html::escape($linkback->label()) . '</li>',
      ];
      $linkback_counter++;
    }
    $form['operation'] = ['#type' => 'hidden', '#value' => 'delete'];

    if (!$linkback_counter) {
      drupal_set_message($this->t('There do not appear to be any linkbacks to delete, or your selected linkback was deleted by another administrator.'));
      $form_state->setRedirect('linkback.admin');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      $this->linkbackStorage->delete($this->linkbacks);
      $count = count($form_state->getValue('linkbacks'));
      $this->logger('content')->notice('Deleted @count linkbacks.', ['@count' => $count]);
      drupal_set_message($this->formatPlural($count, 'Deleted 1 linkback.', 'Deleted @count linkbacks.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

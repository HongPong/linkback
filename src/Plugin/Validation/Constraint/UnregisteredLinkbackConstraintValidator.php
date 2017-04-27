<?php

namespace Drupal\linkback\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UnregisteredLinkback constraint.
 */
class UnregisteredLinkbackConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * Entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkbackStorage;

  /**
   * Entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UnregisteredLinkbackConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkback_entity_storage
   *   The linkback storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_entity_storage
   *   The node storage handler.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity field manager.
   */
  public function __construct(
      EntityStorageInterface $linkback_entity_storage,
      EntityStorageInterface $node_entity_storage,
      EntityFieldManagerInterface $field_manager,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->linkbackStorage = $linkback_entity_storage;
    $this->nodeStorage = $node_entity_storage;
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('linkback'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $source_uri = $entity->get('url')->value;
    $target_id = (int) $entity->get('ref_content')->target_id;

    // Do not allow duplicated linkback registration.
    if (isset($source_uri) && isset($target_id)) {
      $linkbacks = $this->linkbackStorage->loadByProperties(
        [
          'url' => $source_uri,
          'ref_content' => $target_id,
        ]
      );

      if (!empty($linkbacks)) {
        $this->context->buildViolation(
          $constraint->linkbackRegistered,
          [
            '%url' => $source_uri,
            '%ref_content' => $target_id,
          ]
        )
          ->setCause((string) t('The ref-back has previously been registered.'))
          ->setCode(LINKBACK_ERROR_REFBACK_ALREADY_REGISTERED)
          ->addViolation();
      }

      // TODO If content hasn't the receive linkbacks enabled.
      $content = FALSE;
      $receive_allowed = TRUE;

      $field_type = $this->fieldManager->getFieldMapByFieldType('linkback_handlers');
      foreach ($field_type as $entity_type_id => $field) {
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $content = $storage->load($target_id);
        if ($content) {
          $field_name = array_keys($field)[0];
          $field_receive_allowed = $content->get($field_name)->linkback_receive;
          $default = $content->get($field_name)->getFieldDefinition()
            ->getDefaultValueLiteral()[0]['linkback_receive'];
          $receive_allowed = (isset($field_receive_allowed)) ? $field_receive_allowed : $default;
          break;
        }
      }
      if (!$content or !$receive_allowed) {
        // Content doesn't exists or receive not allowed.
        $this->context->buildViolation(
          $constraint->linkbackDisabled,
          [
            '%ref_content' => $target_id,
          ]
        )
          ->setCause((string) t('The ref-back is not allowed or is misconfigured.'))
          ->setCode(LINKBACK_ERROR_LOCAL_NODE_REFBACK_NOT_ALLOWED)
          ->addViolation();
      }
    }
  }

}

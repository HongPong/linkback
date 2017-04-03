<?php

namespace Drupal\linkback_webmention\Plugin\QueueWorker;

use Drupal\Core\Entity\Query\QueryFactory;
use GuzzleHttp\ClientInterface;
use Drupal\linkback_webmention\Event\LinkbackReceiveEvent;
use Drupal\linkback\Entity\Linkback;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Provides base functionality for the LinkbackReceiver Queue Workers.
 */
abstract class LinkbackWebmentionReceiver extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Guzzle Http Client.
   *
   * TODO tasks done by this client must go to linkback service.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Creates a new LinkbackSender object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Entity Field Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity query factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Http client service.
   */
  public function __construct(
      EntityFieldManagerInterface $field_manager,
      EntityTypeManagerInterface $entity_type_manager,
      ContainerAwareEventDispatcher $event_dispatcher,
      QueryFactory $entityQuery,
      ClientInterface $http_client
  ) {
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityQuery = $entityQuery;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
  ) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('entity.query'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $logger = \Drupal::logger('linkback');
    /** @var \Drupal\Core\Entity\EntityInterface|bool $content */
    $content = FALSE;
    $receive_allowed = TRUE;

    $field_type = $this->fieldManager->getFieldMapByFieldType('linkback_handlers');
    foreach ($field_type as $entity_type_id => $field) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $content = $storage->load($data['entity']->id());
      if ($content) {
        $field_name = array_keys($field)[0];
        $field_received_allowed = $content->get($field_name)->linkback_receive;
        $default = $content->get($field_name)->getFieldDefinition()->getDefaultValueLiteral()[0]['linkback_receive'];
        $received_allowed = (isset($field_received_allowed)) ? $field_received_allowed : $default;
        break;
      }
    }

    // First check if there's content to be pinged.
    if (!$content) {
      $logger->notice('@source mentioned non-existant content: @target.', ['@source' => $data['source'], '@target' => $data['target']]);
      return;
    }
    // Check if received is allowed in local content.
    if (!$received_allowed) {
      $logger->notice('@source mentioned a content with incoming mentions disabled: @target.', ['@source' => $data['source'], '@target' => $data['target']]);
      return;
    }

    // The existant linkbacks with these source and target if any.
    $linkbacks = [];
    // Check if linkback exists and attach to event.
    $query = $this->entityQuery->get('linkback');
    $query->condition('ref_content', $data['entity']->id());
    $query->condition('url', $data['source']);
    $entity_ids = $query->execute();
    if ($entity_ids) {
      $linkbacks = Linkback::loadMultiple($entity_ids);
    }

    // Get Response of source.
    // TODO This http fetch must be ported to linkback service.
    try {
      $response = $this->getResponse($data['source']);
    }
    catch (Exception $exception) {
      $logger->notice('Failed to get @source mentioning a @target.', ['@source' => $data['source'], '@target' => $data['target']]);
      return;
    }

    $event = new LinkbackReceiveEvent($data['source'], $data['target'], $content, $response, $linkbacks);
    $this->eventDispatcher->dispatch(LinkbackReceiveEvent::EVENT_NAME, $event);
    // Each linkback submodule needs to apply logic with all collected data,
    // for example check if linkback exists...
  }

  /**
   * Gets a response of the source site.
   *
   * TODO This http fetch must be ported to linkback service.
   *
   * @param string $pagelinkedfrom
   *   The URL of the source site.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Representation of an outgoing, server-side response.
   *
   * @throws Exception
   */
  protected function getResponse($pagelinkedfrom) {
    try {
      $client = $this->httpClient;
      $response = $client->get($pagelinkedfrom, ['headers' => ['Accept' => 'text/plain']]);
    }
    catch (BadResponseException $exception) {
      $response = $exception->getResponse();
      drupal_set_message(t('Failed to fetch url due to HTTP error "%error"', ['%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()]), 'error');
      throw $exception;
    }
    catch (RequestException $exception) {
      drupal_set_message(t('Failed to fetch url due to error "%error"', ['%error' => $exception->getMessage()]), 'error');
      throw $exception;
    }
    return $response;
  }

}

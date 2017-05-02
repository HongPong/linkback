<?php

namespace Drupal\linkback_webmention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Returns responses for webmention endpoint routes.
 */
class EndpointController extends ControllerBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EndpointController object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue Factory to queue incoming mentions.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration Factory to get some settings.
   */
  public function __construct(QueueFactory $queue_factory, ConfigFactoryInterface $config_factory) {
    $this->queueFactory = $queue_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('config.factory')
    );
  }

  /**
   * Presents an endpoint to incoming webmentions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that is being mentioned.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return array
   *   The response of the mention and the endpoint information.
   */
  public function nodeEndpoint(NodeInterface $node, Request $request) {
    $source = "";
    $target = "";

    $markup = t('This is a Webmention endpoint. You should send a POST 
    request to this URL with source and target parameters to send a 
    Webmention. More information at <a href="@url">w3.org</a>.', [
      '@url' =>
      'https://www.w3.org/TR/webmention',
    ]);

    $element = [
      '#markup' => $markup,
    ];
    // TODO INCLUDE DELETE HEADER IF NEEDED.
    if ($request->request->get('source')) {
      $source = $request->request->get('source');
    }
    else {
      // No source.
      $element['#attached']['http_header'][] = ['ContentType',
        'x-application/custom-content-type',
      ];
      $element['#attached']['http_header'][] = ['Status', "400 Bad Request"];
    }
    if ($request->request->get('target')) {
      $target = $request->request->get('target');
    }
    else {
      // No target.
      $element['#attached']['http_header'][] = ['ContentType',
        'x-application/custom-content-type',
      ];
      $element['#attached']['http_header'][] = ['Status', "400 Bad Request"];
    }
    if (!empty($source) && !empty($target)) {
      /** @var QueueInterface $queue */
      $queue = $this->queueFactory->get($this->configFactory->get('linkback.settings')->get('use_cron_received') ? 'cron_linkback_receiver' : 'manual_linkback_receiver');
      $queue->createItem([
        "entity" => $node,
        "target" => $target,
        "source" => $source,
        "handler" => "linkback_webmention",
        "fetch_counter" => 0
      ]);
    }
    return $element;
  }

}

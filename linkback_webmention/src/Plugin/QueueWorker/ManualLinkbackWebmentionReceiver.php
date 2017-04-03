<?php

namespace Drupal\linkback_webmention\Plugin\QueueWorker;

/**
 * A Linkback Receiver that fire the events of receiving webmention linkbacks.
 *
 * Via a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_linkback_webmention_receiver",
 *   title = @Translation("Manual Webmention Linkback Receiver"),
 * )
 */
class ManualLinkbackWebmentionReceiver extends LinkbackWebmentionReceiver {}

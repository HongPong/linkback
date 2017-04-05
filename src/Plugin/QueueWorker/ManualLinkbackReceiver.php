<?php

namespace Drupal\linkback\Plugin\QueueWorker;

/**
 * A Linkback Receiver that fire the events of receiving linkbacks.
 *
 * Via a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_linkback_receiver",
 *   title = @Translation("Manual Linkback Receiver"),
 * )
 */
class ManualLinkbackReceiver extends LinkbackReceiver {}

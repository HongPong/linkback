<?php

namespace Drupal\linkback_webmention\Plugin\QueueWorker;

/**
 * A Linkback Receiver trigger of receiving webmention linkbacks on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_linkback_webmention_receiver",
 *   title = @Translation("Cron Linkback Webmention Receiver"),
 *   cron = {"time" = 20}
 * )
 */
class CronLinkbackWebmentionReceiver extends LinkbackWebmentionReceiver {}

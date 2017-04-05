<?php

namespace Drupal\linkback\Plugin\QueueWorker;

/**
 * A Linkback Receiver trigger of receiving linkbacks on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_linkback_receiver",
 *   title = @Translation("Cron Linkback Receiver"),
 *   cron = {"time" = 20}
 * )
 */
class CronLinkbackReceiver extends LinkbackReceiver {}

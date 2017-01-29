<?php

/**
 * @file
 * inspired by drupal.org/project/webmention
 */


namespace Drupal\linkback_webmention;

use Drupal\Core\Entity\EntityInterface;
use IndieWeb\MentionClient;

class Webmention {

//
//    public static function sendNotification(EntityInterface $entity) {
//        $sourceURL = $entity->toUrl()->setAbsolute(TRUE);
//        $targetURL = 'https://brid.gy/publish/twitter';
//        $client = new MentionClient();
//        $response = $client->sendWebmention($sourceURL->toUriString(), $targetURL);
//    }

    public static function checkRemoteURL(string $remoteURL, boolean $debug) {
        $targetURL = $remoteURL;
        $client = new MentionClient();
        if ($debug) {
            $client::enableDebug();
        }

        $endpoint = $client->discoverWebmentionEndpoint($targetURL);
        drupal_set_message($resultmessage, $type = 'status', $repeat = FALSE);
    }

}
<?php

namespace Drupal\linkback_webmention;

use IndieWeb\MentionClient;

/**
 * WebMention class.
 */
class Webmention {

  /**
   * Something static.
   */
  public static function staticThing() {
    \Drupal::logger('linkback_webmention')->notice('false bool staticThing');
    return FALSE;

  }

  /**
   * Checks remote Url.
   *
   * @param string $remoteURL
   *   The url to check.
   * @param bool $debug
   *   If we need to debug client.
   */
  public static function checkRemoteUrl($remoteURL, $debug) {
    $targetURL = $remoteURL;
    $client = new MentionClient();
    if ($debug) {
      $client::enableDebug();
    }
    $resultmessage = "checkRemoteUrl Result:";
    $endpoint = $client->discoverWebmentionEndpoint($targetURL);
    $resultmessage .= ($endpoint) ? "supports webmention" : "doesn't support webmention";
    drupal_set_message($resultmessage, $type = 'status', $repeat = FALSE);

  }

}

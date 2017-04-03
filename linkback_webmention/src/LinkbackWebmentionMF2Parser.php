<?php

namespace Drupal\linkback_webmention;

use GuzzleHttp\Client;
use Mf2;

/**
 * Provides processing microformats2 array structures.
 *
 * Derived from https://github.com/dshanske/indieweb-post-kinds/blob/master/includes/class-parse-mf2.php
 * and https://github.com/barnabywalters/php-mf-cleaner
 * and https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * and https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php.
 *
 * @package Drupal\linkback_webmention
 */
class LinkbackWebmentionMF2Parser {
  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a service to process mf2 canonical array from mf2 parser.
   *
   * @link https://packagist.org/packages/mf2/mf2
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Fetches the html dom from url.
   *
   * @param string $url
   *   The url to fetch.
   *
   * @return string
   *   The response body casted to string.
   */
  protected function fetch($url) {
    try {
      $response = $this->httpClient->get($url, ['headers' => ['Accept' => 'text/plain']]);
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
    return (string) $response->getBody();
  }

  /**
   * Is string a URL.
   *
   * @param string $string
   *   The url to check.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it's a url.
   */
  protected function isUrl($string) {
    return preg_match('/^https?:\/\/.+\..+$/', $string);
  }

  /**
   * Is this an h-card.
   *
   * @param array $mf
   *   Parsed Microformats Array.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it is h-card.
   */
  protected function isHcard(array $mf) {
    return is_array($mf) and !empty($mf['type']) and is_array($mf['type']) and in_array('h-card', $mf['type']);
  }

  /**
   * Parse Content.
   *
   * @param array $mf
   *   Parsed Microformats Array.
   * @param string $property
   *   The property to parse.
   *
   * @return array
   *   Content array consisting of text and html properties.
   */
  protected function parseHtmlValue(array $mf, $property) {
    if (!array_key_exists($property, $mf['properties'])) {
      return NULL;
    }
    $textcontent = FALSE;
    $htmlcontent = FALSE;
    $content = $mf['properties'][$property][0];
    if (is_string($content)) {
      $textcontent = $content;
    }
    elseif (!is_string($content) && is_array($content) && array_key_exists('value', $content)) {
      if (array_key_exists('html', $content)) {
        $htmlcontent = check_markup($content['html'], 'filtered_html', '', []);
        $htmlcontent = trim($content['html']);
        $textcontent = trim(str_replace('&#xD;', "\r", $content['value']));
      }
      else {
        $textcontent = trim($content['value']);
      }
    }
    $data = ['text' => $textcontent];
    if ($htmlcontent && $textcontent != $htmlcontent) {
      $data['html'] = $htmlcontent;
    }
    return $data;
  }

  /**
   * Iterates over array keys, returns true if has numeric keys.
   *
   * @param array $arr
   *   The array to be checked whether has numeric keys or not.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates array has numeric keys.
   */
  protected function hasNumericKeys(array $arr) {
    foreach ($arr as $key => $val) {
      if (is_numeric($key)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Verifies if $mf hasn't numeric keys, and has a 'properties' key.
   *
   * @param mixed $mf
   *   mixed to be checked whether is Microformat or not.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it is microformat array.
   */
  protected function isMicroformat($mf) {
    return (is_array($mf) and !$this->hasNumericKeys($mf) and !empty($mf['type']) and isset($mf['properties']));
  }

  /**
   * Verifies if $mf has an 'items' key which is also an array, returns true.
   *
   * @param mixed $mf
   *   Array to be checked whether is Microformat collection or not.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it is microformat collection array.
   */
  protected function isMicroformatCollection($mf) {
    return (is_array($mf) and isset($mf['items']) and is_array($mf['items']));
  }

  /**
   * Verifies if array has key 'value' and 'html' set.
   *
   * @param mixed $p
   *   Array to be checked whether is embedded html or not.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it is embedded html.
   */
  protected function isEmbeddedHtml($p) {
    return is_array($p) and !$this->hasNumericKeys($p) and isset($p['value']) and isset($p['html']);
  }

  /**
   * Verifies if property named $propname is in array $mf.
   *
   * @param array $mf
   *   Array to be checked whether has property or not.
   * @param string $propname
   *   The property to search.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it has the specified property.
   */
  protected function hasProp(array $mf, $propname) {
    return !empty($mf['properties'][$propname]) and is_array($mf['properties'][$propname]);
  }

  /**
   * Verifies if rel named $relname is in array $mf.
   *
   * @param array $mf
   *   Array to be checked whether has specified rel or not.
   * @param string $relname
   *   The rel name.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates it has the specified rel.
   */
  protected function hasRel(array $mf, $relname) {
    return !empty($mf['rels'][$relname]) and is_array($mf['rels'][$relname]);
  }

  /**
   * Verifies if is a microformat or embedded html.
   *
   * @param mixed $v
   *   Array to be checked whether is a microformat or embedded html.
   *
   * @return mixed
   *   An associative array containing the keys:
   *     - 'value'.
   *   or v if verification determines FALSE.
   */
  protected function toPlaintext($v) {
    if ($this->isMicroformat($v) or $this->isEmbeddedHtml($v)) {
      return $v['value'];
    }
    return $v;
  }

  /**
   * Returns plaintext of $propName with optional $fallback.
   *
   * @param array $mf
   *   Microformats array to get plaintext property.
   * @param string $propName
   *   The property to get.
   * @param null|string $fallback
   *   Optional fallback if invalid.
   *
   * @return null|string
   *   The property converted to plain or fallback parameter.
   *
   * @link http://php.net/manual/en/function.current.php
   */
  protected function getPlaintext(array $mf, $propName, $fallback = NULL) {
    if (!empty($mf['properties'][$propName]) and is_array($mf['properties'][$propName])) {
      return $this->toPlaintext(current($mf['properties'][$propName]));
    }
    return $fallback;
  }

  /**
   * Converts $propName in $mf into array_map plaintext.
   *
   * @param array $mf
   *   Microformats array to get property to convert each value to plaintext
   *   through array map.
   * @param string $propName
   *   The property to get.
   * @param null|string $fallback
   *   Optional fallback if invalid.
   *
   * @return null|array
   *   The array property with its values converted to plain text.
   */
  protected function getPlaintextArray(array $mf, $propName, $fallback = NULL) {
    if (!is_array($propName)) {
      if (!empty($mf['properties'][$propName]) and is_array($mf['properties'][$propName])) {
        // Return array_map(array(
        // 'Parse_Mf2', 'to_plaintext'),
        // $mf['properties'][$propName]);
        // FIXME chekc if it works as it should.
        return array_map(
        function ($value) {
            return $this->to_plaintext($value);
        }, $mf['properties'][$propName]
        );
      }
    }
    return $fallback;
  }

  /**
   * Return an array of properties, and may contain plaintext content.
   *
   * @param array $mf
   *   Microformats array from where to get properties array.
   * @param array $properties
   *   The properties to get.
   * @param null|string $fallback
   *   Optional fallback if invalid.
   *
   * @return null|array
   *   The array with all specified properties of microformat array.
   */
  protected function getPropArray(array $mf, array $properties, $fallback = NULL) {
    $data = [];
    foreach ($properties as $p) {
      if (array_key_exists($p, $mf['properties'])) {
        foreach ($mf['properties'][$p] as $v) {
          if (is_string($v)) {
            if (!array_key_exists($p, $data)) {
              $data[$p] = [];
            }
            $data[$p][] = $v;
          }
          elseif ($this->isMicroformat($v)) {
            if (($u = $this->getPlaintext($v, 'url')) && $this->isUrl($u)) {
              if (!array_key_exists($p, $data)) {
                $data[$p] = [];
              }
              $data[$p][] = $u;
            }
          }
        }
      }
    }
    return $data;
  }

  /**
   * Converts element to html.
   *
   * @param string|array $v
   *   The element to convert to html.
   *
   * @return string
   *   The html that has been obtained.
   */
  protected function toHtml(array $v) {
    if ($this->isEmbeddedHtml($v)) {
      return $v['html'];
    }
    elseif ($this->isMicroformat($v)) {
      return htmlspecialchars($v['value']);
    }
    return htmlspecialchars($v);
  }

  /**
   * Gets HTML of $propName or if not, $fallback.
   *
   * @param array $mf
   *   Microformats array from where to get html of specified property.
   * @param string $propName
   *   The property name to get.
   * @param null|string $fallback
   *   Optional fallback.
   *
   * @return string|null
   *   The html of microformat array property.
   */
  protected function getHtml(array $mf, $propName, $fallback = NULL) {
    if (!empty($mf['properties'][$propName]) and is_array($mf['properties'][$propName])) {
      return $this->toHtml(current($mf['properties'][$propName]));
    }
    return $fallback;
  }

  /**
   * Gets a summary of the content.
   *
   * @param array $mf
   *   Microformats array from where to get summary.
   * @param array $content
   *   The content element from microformats array.
   *
   * @return null|string
   *   'Summary' element of $mf or a truncated Plaintext of
   *    $mf['properties']['content'] with 19 chars and ellipsis.
   *
   * @deprecated as not often used.
   */
  protected function getSummary(array $mf, array $content = NULL) {
    if ($this->hasProp($mf, 'summary')) {
      return $this->getPlaintext($mf, 'summary');
    }
    if (!$content) {
      $content = $this->parseHtmlValue($mf, 'content');
    }
    $summary = substr($content['text'], 0, 300);
    if (300 < strlen($content['text'])) {
      $summary .= '...';
    }
    return $summary;
  }

  /**
   * Gets the date published of $mf array.
   *
   * @param array $mf
   *   Microformats array from where to get published value.
   * @param bool $ensureValid
   *   Whether to check if valid date or not.
   * @param null|string $fallback
   *   Optional result if date not available.
   *
   * @return string|null
   *   The date of publishing, null if doesn't exist and fallback not defined.
   */
  protected function getPublished(array $mf, bool $ensureValid = FALSE, $fallback = NULL) {
    return $this->getDatetimeProperty('published', $mf, $ensureValid, $fallback);
  }

  /**
   * Gets the date updated of $mf array.
   *
   * @param array $mf
   *   Microformats array from where to get updated value.
   * @param bool $ensureValid
   *   Whether to check if valid date or not.
   * @param string $fallback
   *   Optional result if date not available.
   *
   * @return string|null
   *   The date of publishing, null if doesn't exist and fallback not defined.
   */
  protected function getUpdated(array $mf, bool $ensureValid = FALSE, $fallback = NULL) {
    return $this->getDatetimeProperty('updated', $mf, $ensureValid, $fallback);
  }

  /**
   * Gets the DateTime properties, published or updated, depending on params.
   *
   * @param string $name
   *   Updated or published.
   * @param array $mf
   *   Microformats array from where to get updated value.
   * @param bool $ensureValid
   *   Whether to check if valid date or not.
   * @param string $fallback
   *   Optional result if date not available.
   *
   * @return string|null
   *   The date of publishing or updated, or null if doesn't exist and fallback
   *   not defined.
   */
  protected function getDatetimeProperty($name, array $mf, bool $ensureValid = FALSE, $fallback = NULL) {
    $compliment = 'published' === $name ? 'updated' : 'published';
    if ($this->hasProp($mf, $name)) {
      $date = $this->getPlaintext($mf, $name);
    }
    elseif ($this->hasProp($mf, $compliment)) {
      $date = $this->getPlaintext($mf, $compliment);
    }
    else {
      return $fallback;
    }
    if (!$ensureValid) {
      return $date;
    }
    else {
      try {
        new DateTime($date);
        return $date;
      }
      catch (Exception $e) {
        return $fallback;
      }
    }
  }

  /**
   * True if same hostname is parsed on both.
   *
   * @param string $u1
   *   Url to check.
   * @param string $u2
   *   Url to check.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates they have the same hostname.
   *
   * @link http://php.net/manual/en/function.parse-url.php
   */
  protected function sameHostname($u1, $u2) {
    return parseUrl($u1, PHP_URL_HOST) === parseUrl($u2, PHP_URL_HOST);
  }

  /**
   * Finds author using indieweb author discovery algorithm.
   *
   * @param array $item
   *   The microformat element where to look for author.
   * @param array $mf2
   *   Microformats array from where to get authorship value.
   *
   * @return array
   *   Associative array with:
   *     -Type: card
   *     -Name: string with the name of the author.
   *     -Url: string with the url of the author.
   *     -Photo: string with the url of the avatar image.
   */
  protected function findAuthor(array $item, array $mf2) {
    $author = [
      'type' => 'card',
      'name' => NULL,
      'url' => NULL,
      'photo' => NULL,
    ];
    // Author Discovery
    // http://indieweb,org/authorship
    $authorpage = FALSE;
    if (array_key_exists('author', $item['properties'])) {
      // Check if any of the values of the author property are an h-card.
      foreach ($item['properties']['author'] as $a) {
        if ($this->isHcard($a)) {
          // 5.1 "if it has an h-card, use it, exit.".
          return $a;
        }
        elseif (is_string($a)) {
          if ($this->isUrl($a)) {
            // 5.2 "otherwise if author property is an http(s) URL,
            // let the author-page have that URL".
            $authorpage = $a;
          }
          else {
            // 5.3 "otherwise use the author property as the author name, exit"
            // We can only set the name, no h-card or URL was found.
            $author['name'] = $this->getPlaintext($item, 'author');
            return $author;
          }
        }
        else {
          // This case is only hit when the author property is an mf2 object
          // that is not an h-card.
          $author['name'] = $this->getPlaintext($item, 'author');
          return $author;
        }
      }
    }
    // 6. "if no author page was found" ... check for rel-author link.
    if (!$authorpage) {
      if (isset($mf2['rels']) && isset($mf2['rels']['author'])) {
        $authorpage = $mf2['rels']['author'][0];
      }
    }
    // 7. "if there is an author-page URL" ...
    if ($authorpage) {
      $author['url'] = $authorpage;
      return $author;
    }
  }

  /**
   * Returns array per parse_url standard with pathname key added.
   *
   * @param string $url
   *   The url to parse.
   *
   * @return array
   *   The url parsed as an associative array.
   *
   * @link http://php.net/manual/en/function.parse-url.php
   */
  protected function parseUrl($url) {
    $r = parse_url($url);
    $r['pathname'] = empty($r['path']) ? '/' : $r['path'];
    return $r;
  }

  /**
   * See if urls match for each component of parsed urls. Return true if so.
   *
   * @param string $url1
   *   The url to be checked.
   * @param string $url2
   *   The other url to be checked.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates if urls are matching.
   *
   * @see parseUrl()
   */
  protected function urlsMatch($url1, $url2) {
    $u1 = $this->parseUrl($url1);
    $u2 = $this->parseUrl($url2);
    foreach (array_merge(array_keys($u1), array_keys($u2)) as $component) {
      if (!array_key_exists($component, $u1) or !array_key_exists($component, $u1)) {
        return FALSE;
      }
      if ($u1[$component] != $u2[$component]) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Representative h-card.
   *
   * Given the microformats on a page representing a person or organisation
   * (h-card), find the single h-card which is representative of the page,
   * or null if none is found.
   *
   * @param array $mfs
   *   The parsed microformats of a page to search for a representative h-card.
   * @param string $url
   *   The URL the microformats were fetched from.
   *
   * @return array|null
   *   Either a single h-card array structure, or null if none was found.
   *
   * @see http://microformats.org/wiki/representative-h-card-parsing
   */
  protected function getRepresentativeHcard(array $mfs, $url) {
    $hCardsMatchingUidUrlPageUrl = $this->findMicroformatsByCallable(
        $mfs, function ($hCard) use ($url) {
            return hasProp($hCard, 'uid') and hasProp($hCard, 'url')
            and $this->urlsMatch(getPlaintext($hCard, 'uid'), $url)
            and count(
                array_filter(
                    $hCard['properties']['url'], function ($u) use ($url) {
                        return $this->urlsMatch($u, $url);
                    }
                )
            ) > 0;
        }
    );
    if (!empty($hCardsMatchingUidUrlPageUrl)) {
      return $hCardsMatchingUidUrlPageUrl[0];
    }
    if (!empty($mfs['rels']['me'])) {
      $hCardsMatchingUrlRelMe = $this->findMicroformatsByCallable(
        $mfs, function ($hCard) use ($mfs) {
          if (hasProp($hCard, 'url')) {
            foreach ($mfs['rels']['me'] as $relUrl) {
              foreach ($hCard['properties']['url'] as $url) {
                if ($this->urlsMatch($url, $relUrl)) {
                  return TRUE;
                }
              }
            }
          }
            return FALSE;
        }
      );
      if (!empty($hCardsMatchingUrlRelMe)) {
        return $hCardsMatchingUrlRelMe[0];
      }
    }
    $hCardsMatchingUrlPageUrl = $this->findMicroformatsByCallable(
        $mfs, function ($hCard) use ($url) {
            return hasProp($hCard, 'url')
            and count(
                array_filter(
                    $hCard['properties']['url'], function ($u) use ($url) {
                        return $this->urlsMatch($u, $url);
                    }
                )
            ) > 0;
        }
    );
    if (count($hCardsMatchingUrlPageUrl) === 1) {
      return $hCardsMatchingUrlPageUrl[0];
    }
    // Otherwise, no representative h-card could be found.
    return NULL;
  }

  /**
   * Flattens microformats properties.
   *
   * @param array $mf
   *   The parsed microformats can intake multiple Microformats including
   *   possible MicroformatCollection.
   *
   * @return array
   *   A flat microformat properties array.
   */
  protected function flattenMicroformatProperties(array $mf) {
    $items = [];

    if (!$this->isMicroformat($mf)) {
      return $items;
    }

    foreach ($mf['properties'] as $propArray) {
      foreach ($propArray as $prop) {
        if ($this->isMicroformat($prop)) {
          $items[] = $prop;
          $items = array_merge($items, $this->flattenMicroformatProperties($prop));
        }
      }
    }

    return $items;
  }

  /**
   * Flattens microformats.
   *
   * @param array $mfs
   *   The parsed microformats can intake multiple Microformats including
   *   possible MicroformatCollection.
   *
   * @return array
   *   A flat microformat items array.
   */
  protected function flattenMicroformats(array $mfs) {
    if ($this->isMicroformatCollection($mfs)) {
      $mfs = $mfs['items'];
    }
    elseif ($this->isMicroformat($mfs)) {
      $mfs = [$mfs];
    }

    $items = [];

    foreach ($mfs as $mf) {
      $items[] = $mf;

      $items = array_merge($items, $this->flattenMicroformatProperties($mf));

      if (empty($mf['children'])) {
        continue;
      }

      foreach ($mf['children'] as $child) {
        $items[] = $child;
        $items = array_merge($items, $this->flattenMicroformatProperties($child));
      }
    }

    return $items;
  }

  /**
   * Try to find a microformat type across all the microformats items array.
   *
   * @param array $mfs
   *   Microformats array from where to get microformat type value.
   * @param string $name
   *   The name of type to look for.
   * @param bool $flatten
   *   If we need to flatten the resulting array.
   *
   * @return array
   *   A canonical microformat array.
   */
  protected function findMicroformatsByType(array $mfs, $name, $flatten = TRUE) {
    return $this->findMicroformatsByCallable(
        $mfs, function ($mf) use ($name) {
            return in_array($name, $mf['type']);
        }, $flatten
    );
  }

  /**
   * Can determine if a microformat key with value exists in $mf.
   *
   * @param array $mfs
   *   Microformats array from where to get microformat type value.
   * @param string $propName
   *   The name of the property to look for.
   * @param string $propValue
   *   The value of the specified property to look for.
   * @param bool $flatten
   *   If we need to flatten the result.
   *
   * @return array
   *   A canonical microformat array.
   *
   * @see findMicroformatsByCallable()
   */
  protected function findMicroformatsByProperty(array $mfs, $propName, $propValue, $flatten = TRUE) {
    return $this->findMicroformatsByCallable(
        $mfs, function ($mf) use ($propName, $propValue) {
          if (!hasProp($mf, $propName)) {
            return FALSE;
          }

          if (in_array($propValue, $mf['properties'][$propName])) {
            return TRUE;
          }

            return FALSE;
        }, $flatten
    );
  }

  /**
   * Tries to find a microformat element by the return of callable function.
   *
   * @param array $mfs
   *   Microformats array from where to get values.
   * @param callable $callable
   *   A function or method that will filter properties or type.
   * @param bool $flatten
   *   If we need to flatten the result.
   *
   * @return array
   *   An array with the values of the search.
   *
   * @throw InvalidArgumentException if callable is not callable.
   *
   * @link http://php.net/manual/en/function.is-callable.php
   * @see flattenMicroformats()
   */
  protected function findMicroformatsByCallable(array $mfs, callable $callable, $flatten = TRUE) {
    if (!is_callable($callable)) {
      throw new \InvalidArgumentException('$callable must be callable');
    }

    if ($flatten and ($this->isMicroformat($mfs) or $this->isMicroformatCollection($mfs))) {
      $mfs = $this->flattenMicroformats($mfs);
    }

    return array_values(array_filter($mfs, $callable));
  }

  /**
   * Parses marked up HTML using MF2.
   *
   * @param string $content
   *   The HTML string or DOMDocument object to parse.
   * @param string $url
   *   The URL the input document was found at, for relative URL resolution.
   *
   * @return array
   *   Canonical MF2 array structure.
   */
  public function mf2Parse($content, $url) {
    $parsed = Mf2\parse($content, $url);
    if (!is_array($parsed)) {
      return [];
    }
    $count = count($parsed['items']);
    if (0 == $count) {
      return [];
    }
    if (1 == $count) {
      $item = $parsed['items'][0];
      if (in_array('h-feed', $item['type'])) {
        return ['type' => 'feed'];
      }
      if (in_array('h-card', $item['type'])) {
        return $this->parseHcard($item, $parsed, $url);
      }
      elseif (in_array('h-entry', $item['type']) || in_array('h-cite', $item['type'])) {
        return $this->parseHentry($item, $parsed);
      }
    }
    foreach ($parsed['items'] as $item) {
      if (array_key_exists('url', $item['properties'])) {
        $urls = $item['properties']['url'];
        if (in_array($url, $urls)) {
          if (in_array('h-card', $item['type'])) {
            return $this->parseHcard($item, $parsed, $url);
          }
          elseif (in_array('h-entry', $item['type']) || in_array('h-cite', $item['type'])) {
            return $this->parseHentry($item, $parsed);
          }
        }
      }
    }
  }

  /**
   * Parses an h-entry.
   *
   * @param array $entry
   *   The h-entry to be parsed.
   * @param array $mf
   *   Microformats array from where to get values.
   *
   * @return array
   *   The parsed h-entry.
   *
   * @link http://microformats.org/wiki/h-entry
   */
  private function parseHentry(array $entry, array $mf) {
    // Array Values.
    $properties = [
      'category',
      'invitee',
      'photo',
      'video',
      'audio',
      'syndication',
      'in-reply-to',
      'like-of',
      'repost-of',
      'bookmark-of',
      'tag-of',
    ];
    $data = $this->getPlaintextArray($entry, $properties);
    $data['type'] = 'entry';
    $data['published'] = $this->getPublished($entry);
    $data['updated'] = $this->getUpdated($entry);
    $properties = ['url', 'rsvp', 'featured', 'name'];
    foreach ($properties as $property) {
      $data[$property] = $this->getPlaintext($entry, $property);
    }
    $data['content'] = $this->parseHtmlValue($entry, 'content');
    $data['summary'] = $this->getSummary($entry, $data['content']);
    if (isset($data['name'])) {
      $data['name'] = trim(preg_replace('/https?:\/\/([^ ]+|$)/', '', $data['name']));
    }
    if (isset($mf['rels']['syndication'])) {
      if (isset($data['syndication'])) {
        $data['syndication'] = array_unique(array_merge($data['syndication'], $mf['rels']['syndication']));
      }
      else {
        $data['syndication'] = $mf['rels']['syndication'];
      }
    }
    $author = $this->findAuthor($entry, $mf);
    if ($author) {
      if (is_array($author['type'])) {
        $data['author'] = $this->parseHcard($author, $mf);
      }
      else {
        $author = array_filter($author);
        if (!isset($author['name']) && isset($author['url'])) {
          $content = $this->fetch($author['url']);
          $parsed = Mf2\parse($content, $author['url']);
          $hcard = $this->findMicroformatsByType($parsed, 'h-card');
          if (is_array($hcard) && !empty($hcard)) {
            $hcard = $hcard[0];
          }
          $data['author'] = $this->parseHcard($hcard, $parsed, $author['url']);
        }
        else {
          $data['author'] = $author;
        }
      }
    }
    $data = array_filter($data);
    if (array_key_exists('name', $data)) {
      if (!array_key_exists('summary', $data) || !array_key_exists('content', $data)) {
        unset($data['name']);
      }
    }
    if (isset($data['name']) && isset($data['summary'])) {
      if ($data['name'] == $data['summary']) {
        unset($data['name']);
      }
    }
    return $data;
  }

  /**
   * Parses the h-card.
   *
   * @param array $hcard
   *   The h-card to be parsed.
   * @param array $mf
   *   Microformats array from where to get values.
   * @param bool $authorurl
   *   If there is a matching author URL, use that one.
   *
   * @return array
   *   The parsed h-card.
   *
   * @link http://microformats.org/wiki/h-card
   */
  private function parseHcard(array $hcard, array $mf, $authorurl = FALSE) {
    // If there is a matching author URL, use that one.
    $data = [
      'type' => 'card',
      'name' => NULL,
      'url' => NULL,
      'photo' => NULL,
    ];
    $properties = ['url', 'name', 'photo'];
    foreach ($properties as $p) {
      if ('url' == $p && $authorurl) {
        // If there is a matching author URL, use that one.
        $found = FALSE;
        foreach ($hcard['properties']['url'] as $url) {
          if ($this->isUrl($url)) {
            if ($url == $authorurl) {
              $data['url'] = $url;
              $found = TRUE;
            }
          }
        }
        if (!$found && $this->isUrl($hcard['properties']['url'][0])) {
          $data['url'] = $hcard['properties']['url'][0];
        }
      }
      elseif (($v = $this->getPlaintext($hcard, $p)) !== NULL) {
        // Make sure the URL property is actually a URL.
        if ('url' == $p || 'photo' == $p) {
          if ($this->isUrl($v)) {
            $data[$p] = $v;
          }
        }
        else {
          $data[$p] = $v;
        }
      }
    }
    return array_filter($data);
  }

}

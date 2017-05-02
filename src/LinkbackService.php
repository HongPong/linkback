<?php

namespace Drupal\linkback;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Url;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\linkback\Exception\LinkbackException;
use Psr\Log\LoggerInterface;

/**
 * Class LinkbackService.
 *
 * @package Drupal\linkback
 */
class LinkbackService {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Language manager to retrieve the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Guzzle Http Client Factory.
   *
   * @var GuzzleHttp\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * DOM navigation for HTML and XML documents.
   *
   * @var Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;

  /**
   * Constructs a LinkbackService.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The Http client factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(LoggerInterface $logger, ClientFactory $http_client_factory, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager) {
    $this->logger = $logger;
    $this->httpClientFactory = $http_client_factory;
    $this->crawler = new Crawler();
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * Given a content_id, attempt to lookup its url.
   *
   * @param int $nid
   *   The content id.
   * @param bool $all_langs
   *   If wants all languages urls.
   *
   * @return string|array
   *   The Url of the content id, or array with all languages urls.
   */
  public function getLocalUrl($nid, $all_langs = FALSE) {
    if (!$all_langs) {
      // WITHOUT LANGS
      // TODO DO NOT PRESUPPOSE it is a node entity. ?follow discover strategy
      // as in constranit validator.
      $local_node_url = Url::fromUserInput("/node/{$nid}")->setAbsolute()->toString();
    }
    else {
      // TODO DO NOT PRESUPPOSE it is a node entity. ?follow discover strategy
      // as in constranit validator.
      $langs = $this->languageManager->getLanguages();
      // RELATIVE NO LANG URL.
      $local_node_url = [];
      $local_node_url[] = $this->aliasManager->getAliasByPath("/node/{$nid}");
      global $base_url;
      $local_node_url[] = $base_url . $local_node_url[0];
      foreach ($langs as $language) {
        // ABSOLUTE WITH LANG.
        $local_node_url[] = Url::fromUserInput("/node/{$nid}", ['language' => $language])->setAbsolute()->toString();
        // RELATIVE WITH LANG.
        $local_node_url[] = Url::fromUserInput("/node/{$nid}", ['language' => $language])->toString();
      }
      // ADD URLENCODED OF ALL THE PREVIOUS urls.
      foreach ($local_node_url as $url) {
        $fragmented_url = explode('/', $url);
        end($fragmented_url);
        $last_index = key($fragmented_url);
        $fragmented_url[$last_index] = urlencode($fragmented_url[$last_index]);
        $local_node_url[] = implode('/', $fragmented_url);
      }
    }
    return $local_node_url;
  }

  /**
   * Gets an excerpt of the source site.
   *
   * @param int $nid
   *   The local content id.
   * @param string $pagelinkedfrom
   *   The URL of the source site.
   * @param string $pagelinkedto
   *   The Url of the target site.
   *
   * @return array
   *   An array with title and excerpt or throws exception in case of problems.
   *   [ means LINKBACK_ERROR_REMOTE_URL_MISSING_LINK ].
   *
   * @throws Exception
   */
  public function getRemoteData($nid, $pagelinkedfrom, $pagelinkedto) {
    try {
      $client = $this->httpClientFactory->fromOptions();
      $response = $client->get($pagelinkedfrom, ['headers' => ['Accept' => 'text/plain']]);
      $data = $response->getBody(TRUE);
    }
    catch (BadResponseException $exception) {
      $response = $exception->getResponse();
      $this->logger->error(t('Failed to fetch url due to HTTP error "%error"', ['%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()]), 'error');
      throw $exception;
    }
    catch (RequestException $exception) {
      $this->logger->error(t('Failed to fetch url due to error "%error"', ['%error' => $exception->getMessage()]), 'error');
      throw $exception;
    }

    $title_excerpt = $this->getTitleExcerpt($nid, (string) $data);
    if (!$title_excerpt) {
      $urls = [
        '%linked_from' => $pagelinkedfrom,
        '%linked_to' => $pagelinkedto,
      ];
      throw new LinkbackException(t('No link found in source url %linked_from referencing content with url %linked_to', $urls), LINKBACK_ERROR_REMOTE_URL_MISSING_LINK);
    }
    else {
      return $title_excerpt;
    }
  }

  /**
   * Gets the title and excerpt of the source site.
   *
   * @param int $nid
   *   The local content id.
   * @param string $data
   *   The HTML from the source site.
   *
   * @return array|false
   *   An array with title and excerpt or FALSE in case of problems.
   */
  public function getTitleExcerpt($nid, $data) {
    $this->crawler->clear();
    $this->crawler->addContent($data);

    /* Excerpt part */
    $local_urls = [];
    foreach ($this->getLocalUrl($nid, TRUE) as $local_url) {
      $local_urls[] = "a[href=\"$local_url\"]";
    }
    $local_urls_xpath = implode(',', $local_urls);
    $links = $this->crawler->filter($local_urls_xpath)
      ->first();
    if (iterator_count($links) > 0) {
      $needle = $links->text();
      $context_node = $links->parents()->first()->filter('p');
    }
    else {
      // No link with that href -> spam behavior.
      return FALSE;
    }
    // Drupal native search_excerpt function.
    $context_text = (iterator_count($context_node) > 0) ? search_excerpt($needle, trim($context_node->text())) : "";

    /* Title part*/
    $title_filter = $this->crawler->filterXPath('//title');
    // If no title found we 'll set the url as title;.
    $title = (iterator_count($title_filter) > 0) ? $title_filter->text() : "No title found";

    return [$title, drupal_render($context_text)];
  }

}

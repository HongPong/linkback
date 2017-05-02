<?php

namespace Drupal\linkback_webmention;

use Symfony\Component\DomCrawler\Crawler;
use Psr\Log\LoggerInterface;

/**
 * Class LinkbackWebmentionParser.
 *
 * @package Drupal\linkback_webmention
 */
class LinkbackWebmentionParser {
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * DOM navigation for HTML and XML documents.
   *
   * @var Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;

  /**
   * A microformat parser.
   *
   * @var Drupal\linkback_webmention\LinkbackWebmentionMF2Parser
   */
  protected $mf2Parser;

  /**
   * A RDF Parser.
   *
   * @var Drupal\linkback_webmention\LinkbackWebmentionRDFParser
   */
  protected $rdfParser;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\linkback_webmention\LinkbackWebmentionMF2Parser $mf2_parser
   *   The webmention microformats parser and processor service.
   * @param \Drupal\linkback_webmention\LinkbackWebmentionRDFParser $rdf_parser
   *   The webmention RDF parser and processor service.
   */
  public function __construct(LoggerInterface $logger, LinkbackWebmentionMF2Parser $mf2_parser, LinkbackWebmentionRDFParser $rdf_parser) {
    $this->logger = $logger;
    $this->mf2Parser = $mf2_parser;
    $this->rdfParser = $rdf_parser;
    $this->crawler = new Crawler();
  }

  /**
   * Checks if both source and target urls have http schemas.
   *
   * @param string $sourceUrl
   *   The source url.
   * @param string $targetUrl
   *   The target url.
   *
   * @return bool
   *   TRUE if both have a valid schema.
   */
  public function isValidSchema($sourceUrl, $targetUrl) {
    $source_valid_schema = substr($sourceUrl, 0, 7) == "http://" || substr($sourceUrl, 0, 8) == "https://";
    $target_valid_schema = substr($targetUrl, 0, 7) == "http://" || substr($targetUrl, 0, 8) == "https://";
    return $source_valid_schema && $target_valid_schema;
  }

  /**
   * Check if source really links to target, returning the excerpt.
   *
   * @param string $target
   *   The URL of the target.
   * @param string $body
   *   The HTML from the source site.
   *
   * @return string|false
   *   The excerpt or FALSE if link doesn't exist.
   */
  public function hasLink($target, $body) {
    if ($this->crawler->count() == 0) {
      $this->crawler->addContent($body);
    }

    $target_url = "a[href=\"$target\"]";
    $links = $this->crawler->filter($target_url)
      ->first();
    if (iterator_count($links) > 0) {
      $needle = $links->text();
      // First bet for p parent.
      $context_node = $links->parents()->first()->filter('p');
      if (iterator_count($context_node) < 1) {
        // If not existing try div.
        $context_node = $links->parents()->first()->filter('div');
      }
      if (iterator_count($context_node) < 1) {
        // No tag found get the first found.
        $context_node = $links->parents()->first();
      }
    }
    else {
      // No link with that href -> spam behavior.
      return FALSE;
    }
    if (iterator_count($context_node) > 0) {
      if (empty($needle)) {
        $context_text = trim($context_node->text());
        return $context_text;
      }
      else {
        // Drupal native search_excerpt function.
        $context_text = search_excerpt($needle, trim($context_node->text()));
        return drupal_render($context_text);
      }
    }
    else {
      return "";
    }
  }

  /**
   * Get basic information from the marked up body.
   *
   * @param string $target
   *   The URL of the target. To get excerpt.
   * @param string $body
   *   The HTML from the source site.
   */
  public function getBasicMetainfo($target, $body) {
    // Check if crawler is already loaded.
    if ($this->crawler->count() == 0) {
      $this->crawler->addContent($body);
    }
    /* Title part */
    $title = $this->getTitle();
    /* Summary part*/
    $summary = $this->hasLink($target, $body);

    return ["name" => $title, "summary" => $summary];
  }

  /**
   * Get MF2 Information from the marked up body of the source.
   *
   * @param string $body
   *   The HTML from the source site.
   * @param string $url
   *   The url of the source.
   *
   * @return array
   *   Canonical MF2 array structure.
   */
  public function getMf2Information($body, $url) {
    $mf2Info = $this->mf2Parser->mf2Parse($body, $url);
    return $mf2Info;
  }

  /**
   * Get RDF Information from the marked up body of the source.
   *
   * @param string $body
   *   The HTML from the source site.
   * @param string $url
   *   The url of the source.
   *
   * @return array
   *   Canonical MF2 array structure.
   */
  public function getRdfInformation($body, $url) {
    $mf2Info = $this->rdfParser->getGraph($body, $url);
    return $mf2Info;
  }

  /**
   * Get Title .
   */
  protected function getTitle() {
    $title_filter = $this->crawler->filterXPath('//title');
    return (iterator_count($title_filter) > 0) ? $title_filter->text() : "No title found";
  }

}

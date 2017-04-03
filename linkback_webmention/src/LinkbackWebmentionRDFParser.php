<?php

namespace Drupal\linkback_webmention;

use EasyRdf_Graph;

/**
 * Provides processing RDFa for webmention sources.
 *
 * Using Easyrdf as engine.
 *
 * @package Drupal\linkback_webmention
 */
class LinkbackWebmentionRDFParser {

  /**
   * Constructs a service to process RDF canonical array.
   *
   * @link http://www.easyrdf.org/docs/api
   */
  public function __construct() {
  }

  /**
   * Gets a easyrdf graph from url.
   *
   * @param string $body
   *   The whole html body.
   * @param string $source
   *   The url of the source.
   */
  public function getGraph($body, $source) {
    $meta = [];
    // $schema = new EasyRdf_Graph($source);
    // $dom = $schema->load();
    $schema = new EasyRdf_Graph();
    $schema->parse($body, 'guess', $source);
    $possible_types = [
      "http://schema.org/BlogPosting",
      "http://schema.org/CreativeWork",
      "http://schema.org/Article",
    ];
    if (!($document = $this->getPostType($possible_types, $schema))) {
      return FALSE;
    }
    if ($schema->hasProperty($document, "schema:name")) {
      $meta['name'] = $schema->get($document, "schema:name")->getValue();
    }
    if ($schema->hasProperty($document, "schema:dateCreated")) {
      $meta['updated'] = $schema->get($document, "schema:dateCreated")->getValue();
    }
    if ($schema->hasProperty($document, "schema:author")) {
      $meta['author'] = $schema->get($document, "schema:author");
      if ($schema->hasProperty($meta['author'], "schema:name")) {
        $meta['author_name'] = $schema->get($meta['author'], "schema:name")->getValue();
      }
      if ($schema->hasProperty($meta['author'], "schema:image")) {
        $meta['author_image'] = $schema->get($meta['author'], "schema:image")->getUri();
      }
    }
    if ($schema->hasProperty($document, "schema:text")) {
      $text = $schema->get($document, "schema:text")->getValue();
      $meta['summary'] = $this->getSummary($text);
    }
    return $meta;
    // DO we want some semantic research of links???.
  }

  /**
   * Given an array with possible rdf:types, return the first found.
   *
   * @param array $rdf_types
   *   The rdf possible types.
   * @param \EasyRdf_Graph $graph
   *   The rdf graph.
   *
   * @return string
   *   the document resource type.
   */
  protected function getPostType(array $rdf_types, EasyRdf_Graph $graph) {
    $document = "";
    foreach ($rdf_types as $type) {
      if ($graph->hasProperty($type, '^rdf:type')) {
        return $graph->get($type, '^rdf:type');
      }
    }
    return $document;
  }

  /**
   * Gets a summary of the content.
   *
   * @param string $content
   *   The schema:text.
   *
   * @return null|string
   *   truncated Plaintext of schema:text with 19 chars and ellipsis.
   */
  protected function getSummary($content = NULL) {
    $summary = substr($content, 0, 300);
    if (300 < strlen($content)) {
      $summary .= '...';
    }
    return $summary;
  }

}

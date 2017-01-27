<?php

namespace Drupal\linkback\Entity;

use Drupal\linkback\LinkbackReceivedInterface;
use Drupal\linkback\Exception\LinkbackException;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines the LinkbackReceived entity for received ref-backs.
 *
 * @ingroup linkback
 *
 * @ContentEntityType(
 *   id = "linkback_received",
 *   label = @Translation("Linkback received"),
 *   base_table = "linkback_received",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\linkback\LinkbackViewsData",
 *     "list_builder" = "Drupal\linkback\LinkbackListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\linkback\Form\LinkbackReceivedDeleteForm"
 *     },
 *     "access" = "\Drupal\linkback\LinkbackReceivedAccessControlHandler"
 *   },
 *   links = {
 *     "canonical" = "/linkback/{linkback_received}",
 *     "delete-form" = "/linkback/{linkback_received}/delete",
 *   },
 *   constraints = {
 *     "UnregisteredLinkback" = {}
 *   }
 * )
 */
class LinkbackReceived extends ContentEntityBase implements LinkbackReceivedInterface {

  use EntityChangedTrait;

  /**
   * The error array in format.
   *
   * [ @int code, @string message ].
   *
   * @var array
   */
  protected $lastError;

  /**
   * {@inheritdoc}
   */
  public function getLastError() {
    return $this->lastError;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastError($error_code, $error_message) {
    $this->lastError = [
      $error_code,
      $error_message,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefContent() {
    return $this->get('ref_content')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcerpt() {
    return $this->get('excerpt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler() {
    return $this->get('handler')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrigin() {
    return $this->get('origin')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    $this->get('uuid')->value = $uuid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefContent($ref_content) {
    $this->get('ref_content')->target_id = $ref_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->get('url')->value = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExcerpt($excerpt) {
    $this->get('excerpt')->value = $excerpt;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->get('title')->value = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandler($handler) {
    return $this->get('handler')->value = $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrigin($origin) {
    $this->get('origin')->value = $origin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreated($created) {
    $this->get('created')->value = $created;
    return $this;
  }

  /**
   * Given a content_id, attempt to lookup its url.
   *
   * @param bool $all_langs
   *   If wants all languages urls.
   *
   * @return string|array
   *   The Url of the content id, or array with all languages urls.
   */
  protected function getLocalUrl($all_langs = FALSE) {
    if (!$all_langs) {
      // WITHOUT LANGS
      // TODO DO NOT PRESUPPOSE it is a node entity. ?follow discover strategy
      // as in constranit validator.
      $local_node_url = Url::fromUserInput("/node/{$this->getRefContent()}")->setAbsolute()->toString();
    }
    else {
      // TODO DO NOT PRESUPPOSE it is a node entity. ?follow discover strategy
      // as in constranit validator.
      $langs = \Drupal::languageManager()->getLanguages();
      $current_lang = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
      // RELATIVE NO LANG URL.
      $local_node_url = [];
      $local_node_url[] = \Drupal::service('path.alias_manager')->getAliasByPath("/node/{$this->getRefContent()}", $langcode = NULL);
      global $base_url;
      $local_node_url[] = $base_url . $local_node_url[0];
      foreach ($langs as $code => $language) {
        // ABSOLUTE WITH LANG.
        $local_node_url[] = Url::fromUserInput("/node/{$this->getRefContent()}", ['language' => $language])->setAbsolute()->toString();
        // RELATIVE WITH LANG.
        $local_node_url[] = Url::fromUserInput("/node/{$this->getRefContent()}", ['language' => $language])->toString();
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
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The unique ID for this linkback_received record..'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the linkback_received entity.'))
      ->setReadOnly(TRUE);

    // The content id for this record.
    $fields['ref_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content reference'))
      ->setDescription(t('The content id.'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    $fields['url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL'))
      ->setDescription(t('The fully-qualified URL of the remote url.'))
      ->setRequired(TRUE);

    $fields['excerpt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Excerpt'))
      ->setDescription(t("Excerpt of the third-party's post."))
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t("Title of the third-party's post."))
      ->setRequired(TRUE);

    $fields['handler'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handler'))
      ->setDescription(t("The handler for this linkback."))
      ->setRequired(TRUE);

    $fields['origin'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Origin'))
      ->setDescription(t('Identifier of the origin, such as an IP address or hostname.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the linkback was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the linkback was changed.'));

    return $fields;
  }

  /**
   * Gets an excerpt of the source site.
   *
   * @param string $pagelinkedfrom
   *   The URL of the source site.
   * @param string $pagelinkedto
   *   The Url of the target site.
   *
   * @return array
   *   An array with title and excerpt or throws exception in case of problems.
   *   [ means LINKBACK_ERROR_REMOTE_URL_MISSING_LINK ].
   *
   * @throws
   */
  protected function getRemoteData($pagelinkedfrom, $pagelinkedto) {
    $client = \Drupal::httpClient();
    try {
      $response = $client->get($pagelinkedfrom, array('headers' => array('Accept' => 'text/plain')));
      $data = $response->getBody(TRUE);
    }
    catch (BadResponseException $exception) {
      $response = $exception->getResponse();
      drupal_set_message(t('Failed to fetch url due to HTTP error "%error"', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())), 'error');
      throw $exception;
    }
    catch (RequestException $exception) {
      drupal_set_message(t('Failed to fetch url due to error "%error"', array('%error' => $exception->getMessage())), 'error');
      throw $exception;
    }
    $title_excerpt = $this->getTitleExcerpt((string) $data);
    if (!$title_excerpt) {
      throw new LinkbackException(t('No link found in source url referencing content with id %id', array('%id' => $this->getRefContent())), LINKBACK_ERROR_REMOTE_URL_MISSING_LINK);
    }
    else {
      return $title_excerpt;
    }

  }

  /**
   * Gets the title or excerpt of the source site.
   *
   * @param string $data
   *   The HTML from the source site.
   *
   * @return array|false
   *   An array with title and excerpt or FALSE in case of problems.
   */
  protected function getTitleExcerpt($data) {
    $crawler = new Crawler($data);
    $title_filter = $crawler->filterXPath('//title');
    // If no title found we 'll set the url as title;.
    $title = (iterator_count($title_filter) > 0) ? $title_filter->text() : $this->getUrl();
    /* Excerpt part */
    $local_urls = [];
    foreach ($this->getLocalUrl(TRUE) as $local_url) {
      $local_urls[] = "a[href=\"$local_url\"]";
    }
    $local_urls_xpath = implode(',', $local_urls);
    $links = $crawler->filter($local_urls_xpath)
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
    return [$title, drupal_render($context_text)];
  }

  /**
   * Save a received ref-back.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Invoke validation.
    $this->setValidationRequired(TRUE);
    if ($this->validate()->count() == 0) {
      // Save the entity.
      // Entity presave/update/insert hooks will be invoked by the entity API
      // controller.
      // @see hook_linkback_received_presave()
      // @see hook_linkback_received_insert()
      // @see hook_linkback_received_update()
      \Drupal::logger('linkback')->notice("Tempted linkback could be registered");
    }
    else {
      if ($this->validate()->getByFields(['handler'])->count() == 1) {
        \Drupal::logger('linkback')->error("The refback-handler must be provided.");
        throw new LinkbackException('The refback-handler must be provided.');
      }
      if ($this->validate()->getByFields(['title'])->count() == 1 || $this->validate()->getByFields(['excerpt'])->count() == 1) {
        try {
          $data = $this->getRemoteData($this->getUrl(), $this->getLocalUrl());
          list($title, $excerpt) = $data;
          if (empty($this->getTitle())) {
            $this->setTitle($title);
          }
          if (empty($this->getExcerpt())) {
            $this->setExcerpt($excerpt);
          }

        }
        catch (Exception $exception) {
          throw new LinkbackException($exception->getMessage(), $exception->getCode());
        }
      }
      if ($this->validate()->getEntityViolations()->count() > 0) {
        // COND FOR LINKBACK_ERROR_REFBACK_ALREADY_REGISTERED
        // AND COND FOR LINKBACK_ERROR_LOCAL_NODE_REFBACK_NOT_ALLOWED.
        $violation = $this->validate()->getEntityViolations()[0];
        throw new LinkbackException($violation->getCause(), $violation->getCode());
      }

    }
    $this->setOrigin(\Drupal::request()->getClientIP());
  }

}

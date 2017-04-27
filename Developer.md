## Linkback Developer notes - Introduction

Linkback provides a basis for storage of entities like pingback and webmention activity.
This set of modules includes modern features extending Drupal 8 such as QueueWorker and
Validation classes.

This file provices a quick view of the Linkback code files and their functions, to better
convey the overall architecture. The modules linkback_pingback and linkback_webmention are
not yet included here.

There is a lot remaining to do on Linkback. The src/LinkbackService.php service should manage
several functions that are spread out elsewhere now.

## Files included with Linkback module

### config/install/linkback.settings.yml

Includes use_cron_send and use_cron_received to determine handling of linkback processing.

### src/Controller/AdminController.php

* class AdminController extends ControllerBase
* public function adminPage presents an administrative linkback listing.

### src/Entity/Linkback.php

Defines the Linkback entity.

* class Linkback extends ContentEntityBase implements LinkbackInterface
* public static function preCreate(EntityStorageInterface $storage_controller, array &$values)
* public static function baseFieldDefinitions(EntityTypeInterface $entity_type)

This includes all the fields involved in the Linkback entity.

* public function preSave(EntityStorageInterface $storage)
* public function getTitle()
* public function setTitle($title)
* public function getExcerpt()
* public function getOrigin()
* public function setOrigin($origin)
* public function getRefContent()
* public function setRefContent($ref_content)
* public function getUrl()
* public function setUrl($url)
* public function getHandler()
* public function setHandler($handler)
* public function getCreatedTime()
* public function setCreatedTime($timestamp)
* public function isPublished()
* public function setPublished($published)

### src/Entity/LinkbackViewsData.php

Provides Views data for Linkback entities.

* class LinkbackViewsData extends EntityViewsData implements EntityViewsDataInterface
* public function getViewsData()

### src/Event/LinkbackReceiveEvent.php
* class LinkbackReceiveEvent extends Event
* public function __construct($handler, $source, $target, EntityInterface $local_entity, ResponseInterface $response, array $linkbacks) {
* public function getHandler()
* public function setHandler($handler)
* public function getSource()
* public function setSource($source)
* public function getTarget()
* public function setTarget($target)
* public function getLocalEntity() Getter for the local entity.
* public function setLocalEntity(EntityInterface $localEntity)
* public function getResponse()
* public function setResponse(ResponseInterface $response)
* public function getLinkbacks()
* public function setLinkbacks(array $linkbacks)
### src/Event/LinkbackSendEvent.php
* class LinkbackSendEvent extends Event 
* public function __construct(Url $source, Url $target)
* public function getSource()
* public function setSource(Url $source)
* public function getTarget()
* public function setTarget(Url $target)
### src/Event/LinkbackSendRulesEvent.php

Event that is fired when a linkback needs to be send (rules event).

* class LinkbackSendRulesEvent extends Event
* public function getSource()
* public function setSource(ContentEntityInterface $source)
* public function getTarget()
* public function setTargetUrl(Url $url)
* public function __construct(ContentEntityInterface $source, $target)

### src/Exception/LinkbackException.php
* class LinkbackException extends \Exception

### src/Form/ConfirmDeleteMultiple.php

Provides the linkback multiple delete confirmation form.
* class ConfirmDeleteMultiple extends ConfirmFormBase
* public function __construct(EntityStorageInterface $linkback_storage)
* public static function create(ContainerInterface $container)
* public function getFormId()
* public function getQuestion()
* public function getCancelUrl()
* public function getConfirmText()
* public function buildForm(array $form, FormStateInterface $form_state)
* public function submitForm(array &$form, FormStateInterface $form_state)
### src/Form/LinkbackAdminOverview.php

Provides the linkbacks overview administration form.
* class LinkbackAdminOverview extends FormBase
* public function __construct(
        EntityManagerInterface $entity_manager,
        EntityStorageInterface $linkback_storage,
        DateFormatterInterface $date_formatter,
        ModuleHandlerInterface $module_handler
    )
* public static function create(ContainerInterface $container)
* public function getFormId()
* public function buildForm(array $form, FormStateInterface $form_state, $type = 'local')

Form constructor for the linkback overview administration form.

* public function validateForm(array &$form, FormStateInterface $form_state)
* public function submitForm(array &$form, FormStateInterface $form_state)

### src/Form/LinkbackDeleteForm.php

* class LinkbackDeleteForm extends ContentEntityDeleteForm

### src/Form/LinkbackForm

Form controller for Linkback edit forms.

* class LinkbackForm extends ContentEntityForm
* public function buildForm(array $form, FormStateInterface $form_state)
* public function save(array $form, FormStateInterface $form_state)

### src/Form/LinkbackReceiverQueueForm.php

The class for Linkback receiver queue form. Based on FormBase.

* class LinkbackReceiverQueueForm extends FormBase
* public function __construct(
        QueueFactory $queue,
        QueueWorkerManagerInterface $queue_manager,
        ConfigFactoryInterface $config_factory
    )
* public static function create(ContainerInterface $container)
* protected function getQueue()
* public function getFormId()
* public function buildForm(array $form, FormStateInterface $form_state)
* public function deleteQueue(array &$form, FormStateInterface $form_state)
* public function submitForm(array &$form, FormStateInterface $form_state)

### src/Form/LinkbackSenderQueueForm.php
The class for Linkback sender queue form. Based on FormBase.
* class LinkbackSenderQueueForm extends FormBase
* public function __construct(
        QueueFactory $queue,
        QueueWorkerManagerInterface $queue_manager,
        ConfigFactoryInterface $config_factory
    )
* public static function create(ContainerInterface $container)
* protected function getQueue()
* public function getFormId()
* public function buildForm(array $form, FormStateInterface $form_state)
* public function deleteQueue(array &$form, FormStateInterface $form_state)
* public function submitForm(array &$form, FormStateInterface $form_state)

### src/Form/LinkbackSettingsForm.php
* class LinkbackSettingsForm extends ConfigFormBase
* protected function getEditableConfigNames()
* public function getFormId()
* public function buildForm(array $form, FormStateInterface $form_state)
* public function validateForm(array &$form, FormStateInterface $form_state)

### src/Plugin
### src/Plugin/Field/FieldFormatter/LinkbackFormatter.php
Plugin implementation of the 'linkback_formatter' formatter.
* class LinkbackFormatter extends FormatterBase
* public static function defaultSettings()
* public function settingsForm(array $form, FormStateInterface $form_state)
* public function settingsSummary()
* public function viewElements(FieldItemListInterface $items, $langcode)
* protected function viewValue(FieldItemInterface $item)
Generate the output appropriate for one field item.
### src/Plugin/FieldType/LinkbackHandlerItem.php
Plugin implementation of the 'linkback_handlers' field type.
* class LinkbackHandlerItem extends FieldItemBase
* public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
* public static function schema(FieldStorageDefinitionInterface $field_definition)
* public function isEmpty()
### src/Plugin/Field/FieldWidget/LinkbackDefaultWidget.php
Plugin implementation of the 'linkback_default_widget' widget.
* class LinkbackDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface
* public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        AccountInterface $current_user
    )
* public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    )
* public function settingsForm(array $form, FormStateInterface $form_state)
* public function settingsSummary()
* public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)

### src/Plugin/Menu/LocalTask/QueuedLinkbacks.php
* class QueuedLinkbacks extends LocalTaskDefault implements ContainerFactoryPluginInterface
* public function __construct(
        array $configuration,
        $plugin_id,
        array $plugin_definition,
        EntityStorageInterface $linkback_storage
    )
* public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    )
### src/Plugin/Menu/LocalTask/ReceivedLinkbacks.php

* class ReceivedLinkbacks extends LocalTaskDefault implements ContainerFactoryPluginInterface
* public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    )

* public function __construct(
        array $configuration,
        $plugin_id,
        array $plugin_definition,
        EntityStorageInterface $linkback_storage
    )

Construct the ReceivedLinkbacks object.
    
### src/Plugin/QueueWorker/CronLinkbackReceiver.php
### src/Plugin/QueueWorker/CronLinkbackSender.php
### src/Plugin/QueueWorker/LinkbackReceiver.php
* abstract class LinkbackReceiver extends QueueWorkerBase implements ContainerFactoryPluginInterface {
* public function __construct(
        EntityFieldManagerInterface $field_manager,
        EntityTypeManagerInterface $entity_type_manager,
        ContainerAwareEventDispatcher $event_dispatcher,
        QueryFactory $entityQuery,
        ClientInterface $http_client
    )

public static function create(ContainerInterface $container,
                                    array $configuration,
                                    $plugin_id,
                                    $plugin_definition
                                )
                                
* public function processItem($data)
* protected function getResponse($pagelinkedfrom)

TODO This http fetch must be ported to linkback service.


### src/Plugin/QueueWorker/LinkbackSender.php
* abstract class LinkbackSender extends QueueWorkerBase implements ContainerFactoryPluginInterface
* public function __construct(
        EntityFieldManagerInterface $field_manager,
        EntityTypeManagerInterface $entity_type_manager,
        ContainerAwareEventDispatcher $event_dispatcher
    )
* public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    )
* public function processItem($data)
* protected function getBodyUrls($body)

Get urls from a body html.

### src/Plugin/QueueWorker/ManualLinkbackReceiver.php
### src/Plugin/QueueWorker/ManualLinkbackSender.php

### src/Plugin/Validation/Constraint/UnregisteredLinkbackConstraint.php

* class UnregisteredLinkbackConstraint extends CompositeConstraintBase
* public function coversFields()

### src/Plugin/Validation/Constraint/UnregisteredLinkbackConstraintValidator.php
* class UnregisteredLinkbackConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface
* public function __construct(
        EntityStorageInterface $linkback_entity_storage,
        EntityStorageInterface $node_entity_storage,
        EntityFieldManagerInterface $field_manager,
        EntityTypeManagerInterface $entity_type_manager
    )
* public static function create(ContainerInterface $container)
* public function validate($entity, Constraint $constraint)

### src/Plugin/views/wizard/Linkback.php
* class Linkback extends WizardPluginBase

### src/LinkbackAccessControlHandler.php

Access controller for the Linkback entity.

* class LinkbackAccessControlHandler extends EntityAccessControlHandler
* protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
* protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)

### src/LinkbackHtmlRouteProvider.php

* class LinkbackHtmlRouteProvider extends AdminHtmlRouteProvider
* public function getRoutes(EntityTypeInterface $entity_type)
* protected function getCollectionRoute(EntityTypeInterface $entity_type)
* protected function getAddFormRoute(EntityTypeInterface $entity_type)


### src/LinkbackInterface.php

* interface LinkbackInterface extends ContentEntityInterface, EntityChangedInterface

Provides an interface for defining Linkback entities.

* public function getTitle()
* public function setTitle($title)
* public function getExcerpt()
* public function setExcerpt($excerpt)
* public function getOrigin()
* public function getRefContent()
* public function setRefContent($ref_content)
* public function getUrl()
* public function getHandler()
* public function setHandler($handler)
* public function getCreatedTime()
* public function setCreatedTime($timestamp)
* public function isPublished()
* public function setPublished($published)

### src/LinkbackListBuilder.php

* class LinkbackListBuilder extends EntityListBuilder
* public function buildHeader()
* public function buildRow(EntityInterface $entity)

### src/LinkbackService.php

* class LinkbackService
* public function __construct(LoggerInterface $logger, ClientFactory $http_client_factory, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager)
* public function getLocalUrl($nid, $all_langs = FALSE)
* public function getRemoteData($nid, $pagelinkedfrom, $pagelinkedto)
* public function getTitleExcerpt($nid, $data)

### templates/linkback.html.twig

Default theme implementation to present Linkback data.

### composer.json

### linkback.info.yml

Description of module.

### linkback.links.action.yml

* entity.linkback.add_form

Add Linkback form

### linkback.links.menu.yml

* linkback.settings

### linkback.links.task.yml

Routes to forms, settings and queues.

### linkback.module
* function linkback_help($route_name, RouteMatchInterface $route_match)
* function linkback_entity_insert(EntityInterface $entity)
* function linkback_entity_update(EntityInterface $entity)

### linkback.permissions.yml

See README.md

### linkback.routing.yml

Paths to config and queue control areas.

### linkback.rules.events.yml
* linkback_send

### linkback.services.yml
* logger.channel.linkback
* linkback.default

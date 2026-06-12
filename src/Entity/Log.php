<?php

declare(strict_types=1);

namespace Drupal\log\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\Menu\DefaultEntityLocalTaskProvider;
use Drupal\entity\QueryAccess\UncacheableQueryAccessHandler;
use Drupal\entity\Revision\RevisionableContentEntityBase;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\entity\Routing\RevisionRouteProvider;
use Drupal\entity\UncacheableEntityAccessControlHandler;
use Drupal\entity\UncacheableEntityPermissionProvider;
use Drupal\log\Form\LogForm;
use Drupal\log\LogListBuilder;
use Drupal\log\LogStorage;
use Drupal\log\LogViewsData;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Log entity.
 *
 * @ingroup log
 */
#[ContentEntityType(
  id: 'log',
  label: new TranslatableMarkup('Log'),
  label_collection: new TranslatableMarkup('Logs'),
  label_singular: new TranslatableMarkup('log'),
  label_plural: new TranslatableMarkup('logs'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'bundle' => 'type',
    'label' => 'name',
    'owner' => 'uid',
    'uuid' => 'uuid',
    'langcode' => 'langcode',
  ],
  handlers: [
    'storage' => LogStorage::class,
    'access' => UncacheableEntityAccessControlHandler::class,
    'list_builder' => LogListBuilder::class,
    'permission_provider' => UncacheableEntityPermissionProvider::class,
    'query_access' => UncacheableQueryAccessHandler::class,
    'view_builder' => EntityViewBuilder::class,
    'views_data' => LogViewsData::class,
    'form' => [
      'add' => LogForm::class,
      'edit' => LogForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'default' => AdminHtmlRouteProvider::class,
      'revision' => RevisionRouteProvider::class,
    ],
    'local_task_provider' => [
      'default' => DefaultEntityLocalTaskProvider::class,
    ],
  ],
  links: [
    'canonical' => '/log/{log}',
    'add-page' => '/log/add',
    'add-form' => '/log/add/{log_type}',
    'collection' => '/admin/content/log',
    'delete-form' => '/log/{log}/delete',
    'delete-multiple-form' => '/log/delete',
    'edit-form' => '/log/{log}/edit',
    'revision' => '/log/{log}/revisions/{log_revision}/view',
    'revision-revert-form' => '/log/{log}/revisions/{log_revision}/revert',
    'version-history' => '/log/{log}/revisions',
  ],
  admin_permission: 'administer log',
  collection_permission: 'access log collection',
  permission_granularity: 'bundle',
  bundle_entity_type: 'log_type',
  bundle_label: new TranslatableMarkup('Log type'),
  base_table: 'log',
  data_table: 'log_field_data',
  revision_table: 'log_revision',
  revision_data_table: 'log_field_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count log',
    'plural' => '@count logs',
  ],
  field_ui_base_route: 'entity.log_type.edit_form',
  common_reference_target: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
class Log extends RevisionableContentEntityBase implements LogInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeNamePattern() {
    /** @var \Drupal\log\Entity\LogTypeInterface $type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('log_type')
      ->load($this->bundle());
    return $type->getNamePattern();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    /** @var \Drupal\log\Entity\LogTypeInterface $type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('log_type')
      ->load($this->bundle());
    return $type->label();
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the log. Leave this blank to automatically generate a name.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('Timestamp of the event being logged.'))
      ->setDefaultValueCallback(static::class . '::getRequestTime')
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('state')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates the status of the log.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\log\Entity\Log', 'getWorkflowId']);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the log.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\log\Entity\Log::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 12,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the log was created.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getRequestTime')
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the log was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\log\Entity\LogInterface $log
   *   The log entity.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(LogInterface $log) {
    $workflow = LogType::load($log->bundle())->getWorkflowId();
    return $workflow;
  }

}

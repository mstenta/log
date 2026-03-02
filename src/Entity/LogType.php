<?php

declare(strict_types=1);

namespace Drupal\log\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;
use Drupal\log\Form\LogTypeForm;
use Drupal\log\LogTypeListBuilder;

/**
 * Defines the Log type entity.
 */
#[ConfigEntityType(
  id: 'log_type',
  label: new TranslatableMarkup('Log type'),
  label_collection: new TranslatableMarkup('Log types'),
  label_singular: new TranslatableMarkup('log type'),
  label_plural: new TranslatableMarkup('log types'),
  config_prefix: 'type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => LogTypeListBuilder::class,
    'view_builder' => EntityViewBuilder::class,
    'form' => [
      'add' => LogTypeForm::class,
      'edit' => LogTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'route_provider' => [
      'default' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical' => '/admin/structure/log-type/{log_type}',
    'add-form' => '/admin/structure/log-type/add',
    'edit-form' => '/admin/structure/log-type/{log_type}/edit',
    'delete-form' => '/admin/structure/log-type/{log_type}/delete',
    'collection' => '/admin/structure/log-type',
  ],
  admin_permission: 'administer log types',
  bundle_of: 'log',
  label_count: [
    'singular' => '@count log type',
    'plural' => '@count log types',
  ],
  config_export: [
    'id',
    'label',
    'description',
    'name_pattern',
    'workflow',
    'new_revision',
  ],
)]
class LogType extends ConfigEntityBundleBase implements LogTypeInterface {

  /**
   * The Log type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Log type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this log type.
   *
   * @var string
   */
  protected $description;

  /**
   * Pattern for auto-generating the log name, using tokens.
   *
   * @var string
   */
  protected $name_pattern;

  /**
   * The log type workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * Default value of the 'Create new revision' checkbox of this log type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamePattern() {
    return $this->name_pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // The log type must depend on the module that provides the workflow.
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflow = $workflow_manager->createInstance($this->getWorkflowId());
    $this->calculatePluginDependencies($workflow);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    return $this->set('new_revision', $new_revision);
  }

}

<?php

namespace Drupal\log\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Log type entities.
 *
 * @package Drupal\log\Form
 */
class LogTypeForm extends EntityForm {

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new LogTypeForm object.
   *
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager.
   */
  public function __construct(WorkflowManagerInterface $workflow_manager) {
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $log_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $log_type->label(),
      '#description' => $this->t('Label for the Log type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $log_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\log\Entity\LogType::load',
      ],
      '#disabled' => !$log_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $log_type->getDescription(),
    ];

    // Name pattern is displayed even if the token module is not enabled because
    // the actual token replacement happens in core now.
    $form['name_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name pattern'),
      '#maxlength' => 255,
      '#default_value' => $log_type->getNamePattern(),
      '#description' => $this->t('When filled in, log names of this type will be auto-generated using this naming pattern. Leave empty for not auto generating log names.'),
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $this->workflowManager->getGroupedLabels('log'),
      '#default_value' => $log_type->getWorkflowId(),
      '#description' => $this->t('Used by all logs of this type.'),
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $log_type->shouldCreateNewRevision(),
    ];

    if (\Drupal::service('module_handler')->moduleExists('token')) {
      $form['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['log'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $log_type = $this->entity;
    $status = $log_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Log type.', [
          '%label' => $log_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Log type.', [
          '%label' => $log_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($log_type->toUrl('collection'));
  }

}

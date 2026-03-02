<?php

namespace Drupal\log\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form controller for Log entities.
 *
 * @ingroup log
 */
class LogForm extends ContentEntityForm {

  use AutowireTrait;

  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    protected AccountInterface $currentUser,
    protected DateFormatterInterface $dateFormatter,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\log\Entity\LogInterface $log */
    $log = $this->entity;
    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $log->getChangedTime(),
    ];

    $form += parent::form($form, $form_state);

    // Set autocomplete for log names.
    if (isset($form['name']) && $this->moduleHandler->moduleExists('views')) {
      $num_of_logs = $this->entityTypeManager->getStorage('log')
        ->getQuery()
        ->condition('type', $log->bundle())
        ->count()
        ->accessCheck(TRUE)
        ->execute();
      if ($num_of_logs > 0) {
        $form['name']['widget'][0]['value']['#description'] = FieldFilteredMarkup::create($form['name']['widget'][0]['value']['#description'] . ' ' . $this->t('As you type, frequently used log names will be suggested.'));
        $form['name']['widget'][0]['value']['#autocomplete_route_name'] = 'log.autocomplete.name';
        $form['name']['widget'][0]['value']['#autocomplete_route_parameters'] = ['log_bundle' => $log->bundle()];
      }
    }

    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['meta'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer log'),
    ];
    /** @var \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $status */
    $status = $log->get('status')->first();
    $form['meta']['status'] = [
      '#type' => 'item',
      '#markup' => $status->getLabel(),
      '#access' => !$log->isNew(),
      '#$log' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$log->isNew() ? $this->dateFormatter->format($log->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved']],
    ];
    $form['meta']['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author'),
      '#markup' => $log->getOwner()->getAccountName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__author']],
    ];

    // Author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    $form['#attached']['library'][] = 'core/drupal.form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $entity_url = $this->entity->toUrl()->setAbsolute()->toString();
    $this->messenger()->addMessage($this->t(
        'Saved log: <a href=":url">%label</a>',
        [
          ':url' => $entity_url,
          '%label' => $this->entity->label(),
        ],
      ));
    $form_state->setRedirectUrl($this->entity->toUrl());
    return $status;
  }

}

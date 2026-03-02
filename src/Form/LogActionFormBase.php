<?php

declare(strict_types=1);

namespace Drupal\log\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;

/**
 * Base form class for configurable actions.
 */
abstract class LogActionFormBase extends ConfirmFormBase {

  use AutowireTrait;

  /**
   * The logs to clone.
   *
   * @var \Drupal\log\Entity\LogInterface[]
   */
  protected $logs;

  /**
   * The action id.
   *
   * @var string
   */
  protected $actionId;

  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $user,
  ) {
    $this->logs = $this->tempStoreFactory->get($this->actionId)->get((string) $this->user->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.log.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // PHP CodeSniffer complains about passing an empty string to t(), but we
    // want an empty description, and we must return a TranslatableMarkup.
    // phpcs:ignore
    return $this->t('');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('New date'),
      '#default_value' => new DrupalDateTime('midnight'),
      '#required' => TRUE,
    ];

    $form['revision_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision message'),
      '#description' => $this->t("Optionally add a message to describe this change. This will appear in the log's revisions."),
      '#weight' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStoreFactory->get($this->actionId)->delete((string) $this->user->id());
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

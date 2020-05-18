<?php

namespace Drupal\log\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides a log reschedule confirmation form.
 */
class LogRescheduleActionForm extends LogActionFormBase {

  /**
   * The action id.
   *
   * @var string
   */
  protected $actionId = 'log_reschedule_action';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_reschedule_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->logs), 'Are you sure you want to reschedule this log?', 'Are you sure you want to reschedule these logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reschedule');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->logs)) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $new_date */
      $new_date = $form_state->getValue('date');
      $count = count($this->logs);
      foreach ($this->logs as $log) {
        $log->get('status')->first()->applyTransitionById('to_pending');
        $log->set('timestamp', $new_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
        $log->save();
      }
      $this->messenger()->addMessage($this->formatPlural($count, 'Rescheduled 1 log.', 'Rescheduled @count logs.'));
    }

    parent::submitForm($form, $form_state);
  }

}

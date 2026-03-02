<?php

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that marks a log as done.
 */
#[Action(
  id: 'log_mark_as_done_action',
  label: new TranslatableMarkup('Sets a Log as done'),
  type: 'log',
)]
class LogMarkAsDone extends LogStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'done';

}

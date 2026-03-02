<?php

declare(strict_types=1);

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that marks a log as pending.
 */
#[Action(
  id: 'log_mark_as_pending_action',
  label: new TranslatableMarkup('Sets a Log as pending'),
  type: 'log',
)]
class LogMarkAsPending extends LogStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'pending';

}

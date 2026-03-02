<?php

declare(strict_types=1);

namespace Drupal\log\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that reschedules a log entity.
 */
#[Action(
  id: 'log_reschedule_action',
  label: new TranslatableMarkup('Reschedules a log'),
  confirm_form_route_name: 'log.log_schedule_action_form',
  type: 'log',
)]
class LogReschedule extends LogActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\log\Entity\LogInterface $object */
    $result = $object->get('timestamp')->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}

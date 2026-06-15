<?php

declare(strict_types=1);

namespace Drupal\log\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Access hook implementations for log.
 */
class AccessHooks {

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {

    // Default to neutral.
    $access = AccessResult::neutral();

    // We only care about log entities.
    if ($entity->getEntityTypeId() !== 'log') {
      return $access;
    }

    // Allow access to view revisions if the user has "view all log revisions"
    // permission.
    if (in_array($operation, ['view revision', 'view all revisions'])) {
      $access = AccessResult::allowedIfHasPermission($account, 'view all log revisions');
    }

    // Allow access to revert revisions if the user has "revert all log
    // revisions" permission.
    if ($operation === 'revert') {
      $access = AccessResult::allowedIfHasPermission($account, 'revert all log revisions');
    }

    // Return the access result.
    return $access;
  }

}

<?php

/**
 * @file
 * Post update functions for log module.
 */

declare(strict_types=1);

/**
 * Add revision_data_table to log entity type definition.
 */
function log_post_update_revision_data_table(&$sandbox) {
  $manager = \Drupal::service('entity.definition_update_manager');
  $entity_type = $manager->getEntityType('log');
  $entity_type->set('revision_data_table', 'log_field_revision');
  $manager->updateEntityType($entity_type);
}

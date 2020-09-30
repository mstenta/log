<?php

namespace Drupal\log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines the controller class for logs.
 *
 * This extends the base storage class, adding required special handling for
 * log entities.
 */
class LogStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($update && $this->entityType->isTranslatable()) {
      $this->invokeTranslationHooks($entity);
    }

    parent::doPostSave($entity, $update);

    // If the log name is empty, check to see if the log type has a name pattern
    // for auto-generating a log name.
    // It is not up to this moment that the entity has an id, so the patterns
    // that contain an id need to be here.
    if ($entity->get('name')->isEmpty() && $name_pattern = $entity->getTypeNamePattern()) {
      $old_name = $entity->get('name')->value;
      // Pass in an empty bubbleable metadata object, so we can avoid starting a
      // renderer, for example if this happens in a REST resource creating
      // context.
      $new_name = \Drupal::token()->replace(
        $name_pattern,
        ['log' => $entity],
        [],
        new BubbleableMetadata()
      );
      // Only act if the system is actually changing anything.
      if ($old_name != $new_name) {
        $entity->set('name', $new_name);
        $entity->save();
      }
    }
  }

}

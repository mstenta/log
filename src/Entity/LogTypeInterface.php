<?php

namespace Drupal\log\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining Log type entities.
 */
interface LogTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * Gets the log type's workflow ID.
   *
   * Used by the $log->status field.
   *
   * @return string
   *   The log type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the log type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);

  /**
   * Returns the name pattern for a log type.
   *
   * @return string
   *   The log type name pattern.
   */
  public function getNamePattern();

}

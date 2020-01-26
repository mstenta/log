<?php

namespace Drupal\log;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for the file entity type.
 */
class LogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // @todo
    // Consider integrating reverse views relationships in the future.
    // @see https://www.drupal.org/project/drupal/issues/2706431
    return $data;
  }

}

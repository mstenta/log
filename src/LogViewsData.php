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

    // @TODO
    // Left intentionally blank for integrating reverse views relationships in
    // the future.
    return $data;
  }

}

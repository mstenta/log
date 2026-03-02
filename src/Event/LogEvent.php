<?php

namespace Drupal\log\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\log\Entity\LogInterface;

/**
 * Event that is fired by log save, delete and clone operations.
 */
class LogEvent extends Event {

  /**
   * Log presave event.
   *
   * @deprecated in log:3.1.0 and is removed from log:4.0.0.
   *    Use hook_entity_presave() instead.
   *
   * @see https://www.drupal.org/node/3576562
   */
  const PRESAVE = 'log_presave';

  /**
   * Log insert event.
   *
   * @deprecated in log:3.1.0 and is removed from log:4.0.0.
   *    Use hook_entity_insert() instead.
   *
   * @see https://www.drupal.org/node/3576562
   */
  const INSERT = 'log_insert';

  /**
   * Log update event.
   *
   * @deprecated in log:3.1.0 and is removed from log:4.0.0.
   *    Use hook_entity_update() instead.
   *
   * @see https://www.drupal.org/node/3576562
   */
  const UPDATE = 'log_update';

  /**
   * Log delete event.
   *
   * @deprecated in log:3.1.0 and is removed from log:4.0.0.
   *    Use hook_entity_delete() instead.
   *
   * @see https://www.drupal.org/node/3576562
   */
  const DELETE = 'log_delete';

  /**
   * Log clone event.
   */
  const CLONE = 'log_clone';

  /**
   * The Log entity.
   *
   * @var \Drupal\log\Entity\LogInterface
   */
  public LogInterface $log;

  /**
   * Constructs the object.
   *
   * @param \Drupal\log\Entity\LogInterface $log
   *   The Log entity.
   */
  public function __construct(LogInterface $log) {
    $this->log = $log;
  }

}

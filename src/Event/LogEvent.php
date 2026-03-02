<?php

namespace Drupal\log\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\log\Entity\LogInterface;

/**
 * Event that is fired by log clone operations.
 */
class LogEvent extends Event {

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

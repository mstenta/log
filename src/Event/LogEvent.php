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

  public function __construct(
    public LogInterface $log,
  ) {}

}

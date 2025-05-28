<?php

namespace Drupal\log\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date;

/**
 * Sort handler for logs based on timestamp and id.
 *
 * @ViewsSort("log_standard")
 */
class LogStandardSort extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $this->ensureMyTable();

    switch ($this->options['granularity']) {
      case 'second':
      default:
        $query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
        $query->addOrderBy($this->tableAlias, 'id', $this->options['order']);
        return;

      case 'minute':
        $formula = $this->getDateFormat('YmdHi');
        break;

      case 'hour':
        $formula = $this->getDateFormat('YmdH');
        break;

      case 'day':
        $formula = $this->getDateFormat('Ymd');
        break;

      case 'month':
        $formula = $this->getDateFormat('Ym');
        break;

      case 'year':
        $formula = $this->getDateFormat('Y');
        break;
    }

    $query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_' . $this->field . '_' . $this->options['granularity']);
    $query->addOrderBy($this->tableAlias, 'id', $this->options['order']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDateField() {
    return $this->query->getDateField("$this->tableAlias.timestamp");
  }

}

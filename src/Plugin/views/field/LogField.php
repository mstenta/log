<?php

namespace Drupal\log\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;

/**
 * Field handler to enable custom click-sort behavior for timestamp and id.
 *
 * @ViewsField("log_field")
 */
class LogField extends Standard {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
      $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
      $this->query->addOrderBy($this->tableAlias, 'id', $order);
    }
  }

}

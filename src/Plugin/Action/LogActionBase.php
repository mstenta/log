<?php

namespace Drupal\log\Plugin\Action;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Base class for the configurable actions for logs.
 */
abstract class LogActionBase extends ActionBase implements DependentPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountInterface $user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get($this->getPluginId())->set((string) $this->user->id(), $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\log\Entity\LogInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}

<?php

namespace Drupal\Tests\log\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Log CRUD.
 */
abstract class LogTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'entity',
    'user',
    'log',
    'log_test',
    'field',
    'text',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
    drupal_flush_all_caches();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'access administration pages',
      'administer log',
      'view any log',
      'create default log',
      'view any default log',
      'update own default log',
      'update any default log',
      'delete own default log',
      'delete any default log',
    ];
  }

  /**
   * Creates a log entity.
   *
   * @param array $values
   *   Array of values to feed the entity.
   *
   * @return \Drupal\log\Entity\LogInterface
   *   The log entity.
   */
  protected function createLogEntity(array $values = []) {
    $storage = \Drupal::service('entity_type.manager')->getStorage('log');
    $entity = $storage->create($values + [
      'name' => $this->randomMachineName(),
      'created' => REQUEST_TIME,
      'type' => 'default',
    ]);
    return $entity;
  }

}

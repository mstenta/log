<?php

declare(strict_types=1);

namespace Drupal\Tests\log\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests the Log form actions.
 */
#[Group('log')]
#[RunTestsInSeparateProcesses]
class LogActionsTest extends LogTestBase {

  /**
   * Tests cloning a single log.
   */
  public function testCloneSingleLog() {

    // Create new user.
    $original_user = $this->createUser([], 'Original user');
    $this->assertNotEquals($this->loggedInUser->id(), $original_user->id());

    // Create log.
    $timestamp = \Drupal::time()->getRequestTime();
    $log = $this->createLogEntity([
      'uid' => $original_user->id(),
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_clone_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to clone this log?');
    $this->assertSession()->pageTextContains('New date');

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_clone, 'Clone');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Cloned 1 log');
    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(2, count($logs), 'There are two logs in the system.');
    $this->assertEquals($this->loggedInUser->id(), $logs[2]->getOwnerId(), 'Owner on the new log has been updated.');
    $this->assertEquals($new_timestamp, $logs[2]->get('timestamp')->value, 'Timestamp on the new log has been updated.');
    $this->assertEquals('Cloned from <a href="' . $log->toUrl()->toString() . '">' . $log->label() . '</a>.', $logs[2]->get('revision_log_message')->value, 'A default revision message is set.');
  }

  /**
   * Tests cloning multiple logs.
   */
  public function testCloneMultipleLogs() {

    // Create new user.
    $original_user = $this->createUser([], 'Original user');
    $this->assertNotEquals($this->loggedInUser->id(), $original_user->id());

    // Create logs.
    $timestamp = \Drupal::time()->getRequestTime();
    $original_logs = [];
    for ($i = 0; $i < 3; $i++) {
      $log = $this->createLogEntity([
        'uid' => $original_user->id(),
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();
      $original_logs[$log->id()] = $log;
    }

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(3, $num_of_logs, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_clone_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to clone these logs?');
    $this->assertSession()->pageTextContains('New date');

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[date]'] = date('Y-m-d', $new_timestamp);
    $edit_clone['revision_message'] = 'Lorem ipsum.';
    $this->submitForm($edit_clone, 'Clone');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Cloned 3 logs');

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(6, count($logs), 'There are six logs in the system.');
    // Filter out original logs.
    $cloned_logs = array_filter($logs, function ($log) use ($original_logs) {
      return !array_key_exists($log->id(), $original_logs);
    });
    foreach ($cloned_logs as $log) {
      $this->assertEquals($this->loggedInUser->id(), $log->getOwnerId(), 'Owner on the new log has been updated');
      $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the new log has been updated.');
    }
    // The order of the cloned logs is unpredictable, so we build a list of
    // expected revision messages, and a list of actual revision messages, then
    // sort and compare.
    $expected_revision_messages = array_map(function ($log) {
      return 'Cloned from <a href="' . $log->toUrl()->toString() . '">' . $log->label() . '</a>. Lorem ipsum.';
    }, $original_logs);
    sort($expected_revision_messages);
    $actual_revision_messages = array_map(function ($log) {
      return $log->get('revision_log_message')->value;
    }, $cloned_logs);
    sort($actual_revision_messages);
    $this->assertEquals($expected_revision_messages, $actual_revision_messages, 'The revision message includes a link to the original log and user-provided text.');
  }

  /**
   * Tests rescheduling a single log to an absolute date.
   */
  public function testRescheduleSingleLogAbsolute() {
    $timestamp = \Drupal::time()->getRequestTime();

    $log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to reschedule this log?');
    $this->assertSession()->pageTextContains('New date');

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_reschedule = [];
    $edit_reschedule['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_reschedule, 'Reschedule');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Rescheduled 1 log');

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
    $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
    $new_date = new DrupalDateTime();
    $new_date->setTimestamp($new_timestamp);
    $this->assertEquals('Rescheduled to ' . $new_date->render() . '.', $log->get('revision_log_message')->value, 'A default revision message is set.');
  }

  /**
   * Tests rescheduling multiple logs to an absolute date.
   */
  public function testRescheduleMultipleLogsAbsolute() {
    $timestamp = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));
      $log = $this->createLogEntity([
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();
    }

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(3, $num_of_logs, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to reschedule these logs?');
    $this->assertSession()->pageTextContains('New date');

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['date[date]'] = date('Y-m-d', $new_timestamp);
    $edit_reschedule['revision_message'] = 'Lorem ipsum.';
    $this->submitForm($edit_reschedule, 'Reschedule');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Rescheduled 3 logs');

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(3, count($logs), 'There are three logs in the system.');
    foreach ($logs as $log) {
      $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
      $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
      $new_date = new DrupalDateTime();
      $new_date->setTimestamp($new_timestamp);
      $this->assertEquals('Rescheduled to ' . $new_date->render() . '. Lorem ipsum.', $log->get('revision_log_message')->value, 'The revision message includes the default text and user-provided text.');
    }
  }

  /**
   * Tests rescheduling a single log to an relative date.
   */
  public function testRescheduleSingleLogRelative() {
    $timestamp = \Drupal::time()->getRequestTime();

    $log = $this->createLogEntity([
      'name' => $this->randomMachineName(),
      'created' => \Drupal::time()->getRequestTime(),
      'done' => TRUE,
      'timestamp' => $timestamp,
    ]);
    $log->save();

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to reschedule this log?');
    $this->assertSession()->pageTextContains('New date');

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $this->submitForm($edit_reschedule, 'Reschedule');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log/reschedule');
    $this->assertSession()->pageTextContains('Please enter the amount of time for rescheduling.');

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = 1;
    $edit_reschedule['time'] = 'day';
    $this->submitForm($edit_reschedule, 'Reschedule');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Rescheduled 1 log');

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
    $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
    $new_date = new DrupalDateTime();
    $new_date->setTimestamp($new_timestamp);
    $this->assertEquals('Rescheduled to ' . $new_date->render() . '.', $log->get('revision_log_message')->value, 'A default revision message is set.');
  }

  /**
   * Tests rescheduling multiple logs to an relative date.
   */
  public function testRescheduleMultipleLogsRelative() {
    $timestamp = \Drupal::time()->getRequestTime();
    $expected_timestamps = [];
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime('+1 day', $timestamp);
      $log = $this->createLogEntity([
        'name' => $this->randomMachineName(),
        'created' => \Drupal::time()->getRequestTime(),
        'done' => TRUE,
        'timestamp' => $timestamp,
      ]);
      $log->save();

      // Save the expected timestamp for the log.
      $new_timestamp = strtotime('-1 month', $timestamp);
      $expected_timestamps[$log->id()] = $new_timestamp;
    }

    $num_of_logs = $this->storage->getQuery()->count()->accessCheck(TRUE)->execute();
    $this->assertEquals(3, $num_of_logs, 'There are three logs in the system.');

    $edit = [];
    $edit['action'] = 'log_reschedule_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to reschedule these logs?');
    $this->assertSession()->pageTextContains('New date');

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = -1;
    $edit_reschedule['time'] = 'month';
    $edit_reschedule['revision_message'] = 'Lorem ipsum.';
    $this->submitForm($edit_reschedule, 'Reschedule');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains('Rescheduled 3 logs');

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(3, count($logs), 'There are three logs in the system.');
    foreach ($logs as $log) {
      $this->assertEquals($expected_timestamps[$log->id()], $log->get('timestamp')->value, 'Timestamp on the log has changed.');
      $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
      $new_date = new DrupalDateTime();
      $new_date->setTimestamp($expected_timestamps[$log->id()]);
      $this->assertEquals('Rescheduled to ' . $new_date->render() . '. Lorem ipsum.', $log->get('revision_log_message')->value, 'The revision message includes the default text and user-provided text.');
    }
  }

}

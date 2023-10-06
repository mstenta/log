<?php

namespace Drupal\Tests\log\Functional;

/**
 * Tests the Log form actions.
 *
 * @group Log
 */
class LogActionsTest extends LogTestBase {

  /**
   * Tests cloning a single log.
   */
  public function testCloneSingleLog() {
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
    $edit['action'] = 'log_clone_action';
    $edit['log_bulk_form[0]'] = TRUE;
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to clone this log?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_clone, $this->t('Clone'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Cloned 1 log'));
    $logs = $this->storage->loadMultiple();
    $this->assertEquals(2, count($logs), 'There are two logs in the system.');
    $timestamps = [];
    foreach ($logs as $log) {
      $timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEquals([$timestamp, $new_timestamp], $timestamps, 'Timestamp on the new log has been updated.');
  }

  /**
   * Tests cloning multiple logs.
   */
  public function testCloneMultipleLogs() {
    $expected_timestamps = [];
    $timestamp = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime('+1 day', $timestamp);
      $expected_timestamps[] = $timestamp;
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
    $edit['action'] = 'log_clone_action';
    for ($i = 0; $i < 3; $i++) {
      $edit['log_bulk_form[' . $i . ']'] = TRUE;
    }
    $this->drupalGet('admin/content/log');
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to clone these logs?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_clone = [];
    $edit_clone['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_clone, $this->t('Clone'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Cloned 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEquals(6, count($logs), 'There are six logs in the system.');
    for ($i = 1; $i <= 3; $i++) {
      $expected_timestamps[] = $new_timestamp;
    }
    $log_timestamps = [];
    foreach ($logs as $log) {
      $log_timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEquals($expected_timestamps, $log_timestamps, 'Timestamp on the new logs has been updated.');
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
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to reschedule this log?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $new_timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));

    $edit_reschedule = [];
    $edit_reschedule['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_reschedule, $this->t('Reschedule'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Rescheduled 1 log'));

    $logs = $this->storage->loadMultiple();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
    $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
  }

  /**
   * Tests rescheduling multiple logs to an absolute date.
   */
  public function testRescheduleMultipleLogsAbsolute() {
    $expected_timestamps = [];
    $timestamp = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime(date('Y-n-j', strtotime('+1 day', $timestamp)));
      $expected_timestamps[] = $timestamp;
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
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to reschedule these logs?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['date[date]'] = date('Y-m-d', $new_timestamp);
    $this->submitForm($edit_reschedule, $this->t('Reschedule'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Rescheduled 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEquals(3, count($logs), 'There are three logs in the system.');
    foreach ($logs as $log) {
      $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
      $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
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
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to reschedule this log?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $this->submitForm($edit_reschedule, $this->t('Reschedule'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log/reschedule');
    $this->assertSession()->pageTextContains($this->t('Please enter the amount of time for rescheduling.'));

    $new_timestamp = strtotime('+1 day', $timestamp);

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = 1;
    $edit_reschedule['time'] = 'day';
    $this->submitForm($edit_reschedule, $this->t('Reschedule'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Rescheduled 1 log'));

    $logs = $this->storage->loadMultiple();
    $this->assertEquals(1, $num_of_logs, 'There is one log in the system.');
    $log = reset($logs);
    $this->assertEquals($new_timestamp, $log->get('timestamp')->value, 'Timestamp on the log has changed.');
    $this->assertEquals('pending', $log->get('status')->value, 'Log has been set to pending.');
  }

  /**
   * Tests rescheduling multiple logs to an relative date.
   */
  public function testRescheduleMultipleLogsRelative() {
    $timestamp = \Drupal::time()->getRequestTime();
    $expected_timestamps = [];
    for ($i = 0; $i < 3; $i++) {
      $timestamp = strtotime('+1 day', $timestamp);
      $new_timestamp = strtotime('-1 month', $timestamp);
      $expected_timestamps[] = $new_timestamp;
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
    $this->submitForm($edit, $this->t('Apply to selected items'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Are you sure you want to reschedule these logs?'));
    $this->assertSession()->pageTextContains($this->t('New date'));

    $edit_reschedule = [];
    $edit_reschedule['type_of_date'] = 1;
    $edit_reschedule['amount'] = -1;
    $edit_reschedule['time'] = 'month';
    $this->submitForm($edit_reschedule, $this->t('Reschedule'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/content/log');
    $this->assertSession()->pageTextContains($this->t('Rescheduled 3 logs'));

    $logs = $this->storage->loadMultiple();
    $this->assertEquals(3, count($logs), 'There are three logs in the system.');
    $log_timestamps = [];
    foreach ($logs as $log) {
      $log_timestamps[] = $log->get('timestamp')->value;
    }
    $this->assertEquals($expected_timestamps, $log_timestamps, 'Logs have been rescheduled');
  }

}

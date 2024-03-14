<?php

use CRM_Casereminder_ExtensionUtil as E;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Casereminder_Util_TimeTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp():void {
    parent::setUp();
  }

  public function tearDown():void {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testTime():void {
    $now = CRM_Casereminder_Util_Time::singleton();
    $originalTimestamp = $now->getTimestamp();

    $now->modify('+1 day');
    $modifiedTimestamp = $now->getTimestamp();

    $diffSeconds = ($modifiedTimestamp - $originalTimestamp);
    $this->assertEquals(86400, $diffSeconds, 'After incrementing time by "+1 day", timestasmp is exactly 24 hours in future?');

    $now->modify('+1 day');
    $modifiedTimestamp = $now->getTimestamp();

    $diffSeconds = ($modifiedTimestamp - $originalTimestamp);
    $this->assertEquals((86400 * 2), $diffSeconds, 'After incrementing time by "+1 day" twice, timestasmp is exactly 48 hours in future?');

    $now->reset();
    $resetTimestamp = $now->getTimestamp();

    $this->assertEquals($originalTimestamp, $resetTimestamp, 'Reset timestamp matches original timestamp?');
  }

}

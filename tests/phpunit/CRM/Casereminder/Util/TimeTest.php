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

    // modify() works with basic date math?
    $now->modify('+1 day');
    $modifiedTimestamp = $now->getTimestamp();
    $diffSeconds = ($modifiedTimestamp - $originalTimestamp);
    $this->assertEquals(86400, $diffSeconds, 'After incrementing time by "+1 day", timestasmp is exactly 24 hours in future?');
    
    // modify() works with repeated increments?
    $now->modify('+1 day');
    $modifiedTimestamp = $now->getTimestamp();
    $diffSeconds = ($modifiedTimestamp - $originalTimestamp);
    $this->assertEquals((86400 * 2), $diffSeconds, 'After incrementing time by "+1 day" twice, timestasmp is exactly 48 hours in future?');

    // revert() returns us to our original timestamp?
    $now->revert();
    $revetedTimestamp = $now->getTimestamp();
    $this->assertEquals($originalTimestamp, $revetedTimestamp, 'Reverted timestamp matches original timestamp?');
    
    // reinitialize() wipes an previous 'reset' point and starts with new given time?
    // Test that reinitialization works as expected, with "+10 days" time.
    $now->reinitialize('+10 days');
    $currentTimestamp = time();
    $reinitializedTimestamp = $now->getTimestamp();
    $diffSeconds = ($reinitializedTimestamp - $currentTimestamp);
    $this->assertEquals(864000, $diffSeconds, 'After reinitializing with "+10 days", timestasmp is exactly 10 days in future?');

    // Reinitialized time can be modified as expected?
    $now->modify('+1 day');
    $modifiedTimestamp = $now->getTimestamp();
    $diffSeconds = ($modifiedTimestamp - $reinitializedTimestamp);
    $this->assertEquals(86400, $diffSeconds, 'After incrementing time by "+1 day", timestasmp is exactly 24 hours in future, from reinitialized time?');
    
    // Reinitialized time, after being modified, reverts to the reinitialized time (i.e. '+10 days' after $currentTimestamp.)
    $now->revert();
    $revetedTimestamp = $now->getTimestamp();
    $this->assertEquals($reinitializedTimestamp, $revetedTimestamp, 'Reverted timestamp matches reinitialized timestamp?');

    // Test that reinitialization works as expected with 'empty' time (i.e. "now")
    $now->reinitialize();
    $reinitializedTimestamp = $now->getTimestamp();
    $this->assertEquals(time(), $reinitializedTimestamp, 'After reinitialize to empty time ("now"), timestasmp is exactly now?');
    

    
  }

}

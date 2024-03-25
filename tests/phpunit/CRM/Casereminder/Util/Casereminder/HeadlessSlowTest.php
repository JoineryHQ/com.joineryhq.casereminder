<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Casereminder_ExtensionUtil as E;

/**
 * This test class is slow because setup() clears civicrm cache at least twice:
 * to enable an extension, and to set a setting.
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
class CRM_Casereminder_Util_Casereminder_HeadlessSlowTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use CRM_CasereminderTestTrait;

  private $contactIds = [];
  private $tomorrow;

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
    /* All tests in this class require CASEREMINDER_TESTING_SKIP_EXTERNAL.
     * So if it's not enabled, skip the expensive setup.
     * For more information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      return;
    }

    $this->setupCasereminderTests();
    parent::setUp();

    // $this->now is already tomorrow, so $this->tomorrow should be 2 days future.
    $this->tomorrow = new DateTime('+2 days');

    $this->contactIds['client'] = $this->individualCreate(['email' => 'client@example.com']);
    $this->contactIds['creator']  = $this->individualCreate(['email' => 'creator@example.com']);

    civicrm_api3('setting', 'create', ['mailing_backend' => ['outBound_option' => 5]]);
    civicrm_api3('Extension', 'enable', [
      'keys' => "org.civicoop.emailapi",
    ]);

  }

  public function tearDown():void {
    parent::tearDown();
  }

}

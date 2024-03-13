<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * CaseReminderLogType API Test Case
 * @group headless
 */
class api_v3_CaseReminderLogTypeTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use CRM_CasereminderTestTrait;

  private $caseReminderTypeId;

  /**
   * Set up for headless tests.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp(): void {
    $table = CRM_Core_DAO_AllCoreTables::getTableForEntityName('CaseReminderLogType');
    $this->assertTrue($table && CRM_Core_DAO::checkTableExists($table), 'There was a problem with extension installation. Table for ' . 'CaseReminderLogType' . ' not found.');

    $this->setupCasereminderTests();

    parent::setUp();
    // CareReminderLogCase requires an actual case reminder type, so create that now.

    $caseReminderTypeApiParams = [
      'case_type_id' => 1,
      'case_status_id' => [1, 2],
      'msg_template_id' => 1,
      'recipient_relationship_type_id' => [-1, 14],
      'from_email_address' => '"Mickey Mouse"<mickey@mouse.example.com>',
      'subject' => 'Test subject',
      'dow' => 'monday',
      'max_iterations' => '1000',
      'is_active' => 1,
    ];
    $createCaseReminderType = $this->callAPISuccess('CaseReminderType', 'create', $caseReminderTypeApiParams);
    $this->caseReminderTypeId = $createCaseReminderType['id'];
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testCreateGetDelete(): void {

    $apiParams = [
      'log_time' => '2023-01-01 12:34:56',
      'case_reminder_type_id' => $this->caseReminderTypeId,
      'action' => 'TESTING',
    ];
    $created = $this->callAPISuccess('CaseReminderLogType', 'create', $apiParams);

    $this->assertTrue(is_numeric($created['id']));

    $get = $this->callAPISuccess('CaseReminderLogType', 'get', []);
    $this->assertEquals(1, $get['count']);
    $testParams = [];
    $testParams['id'] = $created['id'];
    $testParams += $apiParams;
    $this->assertEquals($get['values'][$created['id']], $testParams);

    $this->callAPISuccess('CaseReminderLogType', 'delete', [
      'id' => $created['id'],
    ]);
  }

  public function testDefaultTimestamp(): void {
    $apiParams = [
      'case_reminder_type_id' => $this->caseReminderTypeId,
      'action' => 'TESTING',
    ];
    $created = $this->callAPISuccess('CaseReminderLogType', 'create', $apiParams);
    $caseReminderLogTypeCreate = $this->callAPISuccess('CaseReminderLogType', 'create', $apiParams);
    $caseReminderLogType = reset($caseReminderLogTypeCreate['values']);
    $this->assertEquals($this->now->getMysqlDatetime(), $caseReminderLogType['log_time'], 'Default log_time for CaseReminderLogType is correct.');
  }

}

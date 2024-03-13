<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * CaseReminderLogCase API Test Case
 * @group headless
 */
class api_v3_CaseReminderLogCaseTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use CRM_CasereminderTestTrait;

  private $contactIds = [];
  private $caseReminderTypeId;
  private $caseId;

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
    $table = CRM_Core_DAO_AllCoreTables::getTableForEntityName('CaseReminderLogCase');
    $this->assertTrue($table && CRM_Core_DAO::checkTableExists($table), 'There was a problem with extension installation. Table for ' . 'CaseReminderLogCase' . ' not found.');

    $this->setupCasereminderTests();

    parent::setUp();

    $this->contactIds['client'] = $this->individualCreate();
    $this->contactIds['creator']  = $this->individualCreate();

    // CareReminderLogCase requires an actual case reminder type, so create that now.
    $created = $this->createCaseReminderType();
    $this->caseReminderTypeId = $created['id'];

    // CaseReminderLogCase requires an actual case, so create that now.
    $createdCase = $this->createCase($this->contactIds['creator'], $this->contactIds['creator']);
    $this->caseId = $createdCase['id'];
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
      'case_id' => $this->caseId,
      'action' => 'TESTING',
    ];
    $created = $this->callAPISuccess('CaseReminderLogCase', 'create', $apiParams);

    $this->assertTrue(is_numeric($created['id']));

    $get = $this->callAPISuccess('CaseReminderLogCase', 'get', []);
    $this->assertEquals(1, $get['count'], 'Found exactly one CaseReminderLogCase');
    $testParams = [];
    $testParams['id'] = $created['id'];
    $testParams += $apiParams;
    $this->assertEquals($get['values'][$created['id']], $testParams);

    $this->callAPISuccess('CaseReminderLogCase', 'delete', [
      'id' => $created['id'],
    ]);
  }

  public function testDefaultTimestamp(): void {
    $apiParams = [
      'case_reminder_type_id' => $this->caseReminderTypeId,
      'case_id' => $this->caseId,
      'action' => 'TESTING',
    ];
    $caseReminderLogCaseCreate = $this->callAPISuccess('CaseReminderLogCase', 'create', $apiParams);
    $caseReminderLogCase = reset($caseReminderLogCaseCreate['values']);
    $this->assertEquals($this->now->getMysqlDatetime(), $caseReminderLogCase['log_time'], 'Default log_time for CaseReminderLogCase is correct.');
  }

}

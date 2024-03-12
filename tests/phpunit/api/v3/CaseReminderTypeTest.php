<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * CaseReminderType API Test Case
 * @group headless
 */
class api_v3_CaseReminderTypeTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use CRM_CasereminderTestTrait;

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
    $table = CRM_Core_DAO_AllCoreTables::getTableForEntityName('CaseReminderType');
    $this->assertTrue($table && CRM_Core_DAO::checkTableExists($table), 'There was a problem with extension installation. Table for ' . 'CaseReminderType' . ' not found.');

    $this->setupCasereminderTests();

    parent::setUp();
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
    $caseReminderTypeCreate = $this->callAPISuccess('CaseReminderType', 'create', $apiParams);
    $this->assertTrue(is_numeric($caseReminderTypeCreate['id']));
    $created = $caseReminderTypeCreate['values'][$caseReminderTypeCreate['id']];

    $get = $this->callAPISuccess('CaseReminderType', 'get', []);
    $this->assertEquals(1, $get['count']);
    $testParams = [];
    $testParams['id'] = $created['id'];
    $testParams += $apiParams;
    $this->assertEquals($get['values'][$created['id']], $testParams);

    $this->callAPISuccess('CaseReminderType', 'delete', [
      'id' => $created['id'],
    ]);
  }

  /**
   * api should accept case_status_id values as integer or string(name)
   */
  public function testCaseTypeIdCanBeStringName(): void {
    $apiParams = [
      'case_type_id' => 'housing_support',
      'case_status_id' => [1, 2],
      'msg_template_id' => 1,
      'recipient_relationship_type_id' => [-1, 14],
      'from_email_address' => '"Mickey Mouse"<mickey@mouse.example.com>',
      'subject' => 'Test subject',
      'dow' => 'monday',
      'max_iterations' => '1000',
      'is_active' => 1,
    ];
    $caseReminderTypeCreate = $this->callAPISuccess('CaseReminderType', 'create', $apiParams);
    $this->assertTrue(is_numeric($caseReminderTypeCreate['id']), 'Created entity has numeric id.');
    $this->assertEquals(1, count($caseReminderTypeCreate['values']), 'One entity created.');
  }

}

<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * CaseReminderJobRecipient API Test Case
 * @group headless
 */
class api_v3_CaseReminderJobRecipientTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use CRM_CasereminderTestTrait;

  private $contactIds = [];
  private $caseReminderJobId;
  private $caseId;
  private $caseClientEmail;

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
    $table = CRM_Core_DAO_AllCoreTables::getTableForEntityName('CaseReminderJobRecipient');
    $this->assertTrue($table && CRM_Core_DAO::checkTableExists($table), 'There was a problem with extension installation. Table for ' . 'CaseReminderJobRecipient' . ' not found.');

    $this->setupCasereminderTests();

    // Must create a remindertype first.
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

    // Also must create a job first.
    $apiParams = [
      'reminder_type_id' => $caseReminderTypeCreate['id'],
    ];
    $caseReminderJobCreate = $this->callAPISuccess('CaseReminderJob', 'create', $apiParams);
    $this->caseReminderJobId = $caseReminderJobCreate['id'];

    // create contacts for a case.
    $this->caseClientEmail = 'client_hekRiH5GL5xZ6z0JWEvmk8rOmjV3YvbV@example.com';
    $this->contactIds['client'] = $this->individualCreate(['email' => $this->caseClientEmail]);
    $this->contactIds['creator']  = $this->individualCreate(['email' => 'creator@example.com']);
    $this->contactIds['role14']  = $this->individualCreate(['email' => 'caseRole14@example.com']);

    // create a case.
    $case = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'case_type_id' => 1,
      'subject' => "TESTING:type=1",
    ]);
    $this->caseId = $case['id'];

    // Ensure there's a logged-in user -- required for addCaseRoleContact().
    $this->createLoggedInUser();
    // add contact to case.
    $this->addCaseRoleContact($case['id'], $this->contactIds['client'], 14, $this->contactIds['role14']);

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
    // Create a job recipient, populating all fields.
    $apiParams = [
      'job_id' => $this->caseReminderJobId,
      'case_id' => $this->caseId,
      'contact_id' => $this->contactIds['client'],
      'is_case_client' => '0',
      'relationship_type_id' => 14,
      'sent_to' => $this->caseClientEmail,
      'status' => 'this is a status string',
      'status_time' => CRM_Utils_Date::mysqlToIso(CRM_Utils_Date::currentDBDate()),
    ];
    $caseReminderJobRecipientCreate = $this->callAPISuccess('CaseReminderJobRecipient', 'create', $apiParams);

    $this->assertTrue(is_numeric($caseReminderJobRecipientCreate['id']));
    $created = $caseReminderJobRecipientCreate['values'][$caseReminderJobRecipientCreate['id']];

    $get = $this->callAPISuccess('CaseReminderJobRecipient', 'get', []);
    $getValues = $get['values'][$created['id']];

    $this->assertEquals(1, $get['count']);
    $testParams = [];
    $testParams['id'] = $created['id'];
    $testParams += $apiParams;
    $this->assertEquals($getValues, $testParams);

    $this->callAPISuccess('CaseReminderJobRecipient', 'delete', [
      'id' => $created['id'],
    ]);
  }

  public function testCreateRequiresRelationshipTypeIdIfNotIsCaseClient(): void {
    // Create a job recipient, populating most fields.
    $apiParams = [
      'job_id' => $this->caseReminderJobId,
      'case_id' => $this->caseId,
      'contact_id' => $this->contactIds['client'],
      'is_case_client' => '0',
    ];
    try {
      $caseReminderJobRecipientCreate = $this->callAPISuccess('CaseReminderJobRecipient', 'create', $apiParams);
    }
    catch (Exception $e) {
      $this->assertStringContainsString('Must specify relationship_type_id if not is_case_client', $e->getMessage(), 'API fails with "max length" error message?');
    }

    $apiParams['is_case_client'] = 1;
    $caseReminderJobRecipientCreate = $this->callAPISuccess('CaseReminderJobRecipient', 'create', $apiParams);

    $apiParams['is_case_client'] = 0;
    $apiParams['relationship_type_id'] = 14;
    $caseReminderJobRecipientCreate = $this->callAPISuccess('CaseReminderJobRecipient', 'create', $apiParams);
  }

  public function testCreateProvidesNowDateForStatusChange(): void {
    // Create a job recipient, populating status but not status_time.
    $statusString = 'TEST_STATUS';
    $apiParams = [
      'job_id' => $this->caseReminderJobId,
      'case_id' => $this->caseId,
      'contact_id' => $this->contactIds['client'],
      'is_case_client' => '0',
      'status' => $statusString,
    ];
    $caseReminderJobRecipientCreate = $this->callAPISuccess('CaseReminderJobRecipient', 'create', $apiParams);
    $caseReminderJobRecipient = $this->callAPISuccess('CaseReminderJobRecipient', 'getsingle', ['id' => $caseReminderJobRecipientCreate['id']]);

    $this->assertEquals($statusString, $caseReminderJobRecipient['status'], 'Status was saved correctly?');

    $nowSeconds = strtotime(CRM_Utils_Date::currentDBDate());
    $statusSeconds = strtotime($caseReminderJobRecipient['status_time']);
    $diffSeconds = ($nowSeconds - $statusSeconds);
    $this->assertLessThan(3, $diffSeconds);

    return;
  }

}

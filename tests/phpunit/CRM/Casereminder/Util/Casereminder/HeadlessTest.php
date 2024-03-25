<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Casereminder_ExtensionUtil as E;

/**
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
class CRM_Casereminder_Util_Casereminder_HeadlessTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
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

    $this->setupCasereminderTests();
    parent::setUp();

    // $this->now is already tomorrow, so $this->tomorrow should be 2 days future.
    $this->tomorrow = new DateTime('+2 days');

    $this->contactIds['client'] = $this->individualCreate(['email' => 'client@example.com']);
    $this->contactIds['creator']  = $this->individualCreate(['email' => 'creator@example.com']);

  }

  public function tearDown():void {
    parent::tearDown();
  }

  public function testGetNowReminderTypesReturnsOnlyActive() {
    // Create a few reminder types
    // Active
    $reminderTypeActive = $this->createCaseReminderType([
      'subject' => 'Subject:active',
      'is_active' => 1,
    ]);

    // Inactive
    $reminderTypeInactive = $this->createCaseReminderType([
      'subject' => 'Subject:inactive',
      'is_active' => 0,
    ]);

    $reminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(1, count($reminderTypes), 'One reminder type found.');
    $reminderType = reset($reminderTypes);
    $this->assertEquals($reminderTypeActive['subject'], $reminderType['subject'], 'Reminder type subject is correct');
  }

  public function testGetNowReminderTypesReturnsOnlyToday() {
    // Create a few reminder types
    // Active
    $reminderTypeToday = $this->createCaseReminderType([
      'subject' => 'Subject:today',
      'dow' => $this->now->getDayOfWeek(),
      'is_active' => 1,
    ]);

    // Tomorrow
    $reminderTypeTomorrow = $this->createCaseReminderType([
      'subject' => 'Subject:tomorrow',
      'dow' => $this->tomorrow->format('l'),
    ]);

    $reminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(1, count($reminderTypes), 'One reminder type found.');
    $reminderType = reset($reminderTypes);
    $this->assertEquals($reminderTypeToday['subject'], $reminderType['subject'], 'Reminder type subject is correct');
  }

  public function testGetReminderTypeCasesHonorsCaseType() {
    // Create cases with different types
    $caseType1 = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'case_type_id' => 1,
      'subject' => "TESTING:type=1",
    ]);

    // Create caseremindertype specifying case type 1.
    $reminderTypeCaseType1 = $this->createCaseReminderType([
      'case_type_id' => 1,
      'subject' => 'Subject:case-type=1',
    ]);
    $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($reminderTypeCaseType1);
    $this->assertEquals(1, count($reminderTypeCases), 'One case found, limiting by case-type=1');
    $reminderTypeCase = reset($reminderTypeCases);
    $this->assertEquals($caseType1['subject'], $reminderTypeCase['subject'], 'Reminder type subject is correct');

    // Create caseremindertype specifying case type 2.
    $reminderTypeCaseType2 = $this->createCaseReminderType([
      'case_type_id' => 2,
      'subject' => 'Subject:case-type=2',
    ]);
    $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($reminderTypeCaseType2);
    $this->assertEquals(0, count($reminderTypeCases), 'Zero cases found, limiting by case-type=2');
  }

  public function testGetReminderTypeCasesHonorsIsDeleted() {
    // Create non-deleted case with type = 1
    $caseNotDeleted = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'subject' => "TESTING:case not deleted",
    ]);
    // Create deleted case with type = 1
    $caseDeleted = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'subject' => "TESTING:case deleted",
      'is_deleted' => 1,
    ]);

    // Create caseremindertype specifying case type 1.
    $reminderTypeCase = $this->createCaseReminderType();
    $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($reminderTypeCase);
    $this->assertEquals(1, count($reminderTypeCases), 'One non-deleted case found?');
    $reminderTypeCase = reset($reminderTypeCases);
    $this->assertEquals($caseNotDeleted['subject'], $reminderTypeCase['subject'], 'Non-deleted case was returned?');
  }

  public function testGetReminderTypeCasesHonorsStatus() {
    // Create non-deleted case with status = 1
    $caseStatus1 = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'status_id' => 1,
      'subject' => "TESTING: status = 1",
    ]);
    // Create non-deleted case with status = 2
    $caseStatus2 = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'status_id' => 2,
      'subject' => "TESTING: status = 2",
    ]);
    // Create non-deleted case with status = 3
    $caseStatus3 = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'status_id' => 3,
      'subject' => "TESTING: status = 3",
    ]);

    // Create caseremindertype specifying stastus types 1, 2.
    $reminderTypeCase = $this->createCaseReminderType([
      'case_status_id' => [1, 2],
      'subject' => 'Subject: case-status-type=[1,2]',
    ]);

    $reminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $reminderType = reset($reminderTypes);
    $cases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($reminderType);
    $this->assertEquals(2, count($cases), 'Two case found for reminder type with case_status_id=[1,2].');
    $caseSubjects = CRM_Utils_Array::collect('subject', $cases);
    $this->assertContains($caseStatus1['subject'], $caseSubjects, 'Case with status 1 was returned?');
    $this->assertContains($caseStatus2['subject'], $caseSubjects, 'Case with status 2 was returned?');
    $this->assertNotContains($caseStatus3['subject'], $caseSubjects, 'Case with status 3 was NOT returned?');
  }

  public function testBuildRecipientListIsCorrect() {

    // create case
    $case = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'case_type_id' => 1,
      'subject' => "TESTING:type=1",
    ]);

    // Create contacts for roles.
    $role14ContactId = $this->individualCreate();
    $role12ContactId = $this->individualCreate();

    // Ensure there's a logged-in user -- required for addCaseRoleContact().
    $this->createLoggedInUser();

    // Add contacts in case roles.
    $this->addCaseRoleContact($case['id'], $this->contactIds['client'], 12, $role12ContactId);
    $this->addCaseRoleContact($case['id'], $this->contactIds['client'], 14, $role14ContactId);

    // Create caseremindertype specifying recpient roles -1 and 14.
    $reminderType = $this->createCaseReminderType([
      'case_type_id' => 1,
      'subject' => 'Subject:case-type=1',
      'recipient_relationship_type_id' => [-1, 14],
    ]);

    $recpientRolePerCid = CRM_Casereminder_Util_Casereminder::buildRecipientList($case, $reminderType);
    $this->assertEquals(-1, $recpientRolePerCid[$this->contactIds['client']], 'Recipient list contains role -1 for client.');
    $this->assertEquals(14, $recpientRolePerCid[$role14ContactId], 'Recipient list contains role 14 for "role 14" contact.');
    $this->assertArrayNotHasKey($role12ContactId, $recpientRolePerCid, 'Recipient list doees not contain "role 12" contact.');
  }

  public function testSplitFromEmail() {
    $email = '"Mickey Mouse"<mickey@mouse.example.com>';
    $expected = [
      'name' => 'Mickey Mouse',
      'email' => 'mickey@mouse.example.com',
    ];
    $actual = CRM_Casereminder_Util_Casereminder::splitFromEmail($email);
    $this->assertEquals($expected, $actual, 'Address specifier correctly split.');
  }

  public function testPrepCaseReminderSendingParams() {
    // create case
    $case = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'case_type_id' => 1,
      'subject' => "TESTING:type=1",
    ]);

    // Create caseremindertype specifying recpient roles -1 and 14.
    $reminderTypeParams = [
      'case_type_id' => 1,
      'subject' => 'Subject:case-type=1',
      'recipient_relationship_type_id' => [-1, 14],
      'msg_template_id' => 1,
      'from_email_address' => '"Superman"<s@superfriends.example.com>',
    ];
    $reminderType = $this->createCaseReminderType($reminderTypeParams);

    $actualParams = CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams($case, $reminderType);
    $expectedParams = [
      'template_id' => $reminderTypeParams['msg_template_id'],
      'case_id' => $case['id'],
      'create_activity' => TRUE,
      'activity_details' => 'html',
      'from_name' => 'Superman',
      'from_email' => 's@superfriends.example.com',
      'subject' => $reminderTypeParams['subject'],
    ];
    $this->assertEquals($expectedParams, $actualParams, 'reminder Send Params are correct');
  }

  public function testReminderTypeCompletedToday() {
    // Active
    $reminderType = $this->createCaseReminderType();
    $completedTodayFalse = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($reminderType);
    $this->assertFalse($completedTodayFalse, 'Freshly created reminder type is NOT completed today');

    CRM_Casereminder_Util_Log::logReminderType($reminderType['id'], CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_BEGIN);
    $completedTodayFalse = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($reminderType);
    $this->assertFalse($completedTodayFalse, 'Reminder type log BEGIN today is NOT COMPLETE today');

    CRM_Casereminder_Util_Log::logReminderType($reminderType['id'], CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE);
    $completedTodayTrue = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($reminderType);
    $this->assertTrue($completedTodayTrue, '"Completed"-logged reminder type IS completed today');

  }

  public function testReminderTypeCaseReachedMaxIterations() {
    $case = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], []);

    $reminderTypeDefault = $this->createCaseReminderType([]);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeDefault, $case);
    $this->assertFalse($reachedMax, 'Remindertype with max_iterations="" DOES NOT have max iterations?');

    $reminderTypeMaxZero = $this->createCaseReminderType([
      'max_iterations' => '0',
    ]);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxZero, $case);
    $this->assertFalse($reachedMax, 'Remindertype with max_iterations="0" DOES NOT have max iterations?');

    $reminderTypeMaxOne = $this->createCaseReminderType([
      'max_iterations' => '1',
    ]);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxOne, $case);
    $this->assertFalse($reachedMax, 'Fresh Remindertype with max_iterations="1" DOES NOT have max iterations?');

    CRM_Casereminder_Util_Log::logReminderCase($reminderTypeMaxOne['id'], CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $case['id']);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxOne, $case);
    $this->assertTrue($reachedMax, 'Remindertype with max_iterations="1" and 1 log DOES have max iterations?');

    CRM_Casereminder_Util_Log::logReminderCase($reminderTypeMaxOne['id'], CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $case['id']);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxOne, $case);
    $this->assertTrue($reachedMax, 'Remindertype with max_iterations="1" and 2 log DOES have max iterations?');

    $reminderTypeMaxTwo = $this->createCaseReminderType([
      'max_iterations' => '2',
    ]);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxTwo, $case);
    $this->assertFalse($reachedMax, 'Fresh Remindertype with max_iterations="2" DOES NOT have max iterations?');

    CRM_Casereminder_Util_Log::logReminderCase($reminderTypeMaxTwo['id'], CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $case['id']);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxTwo, $case);
    $this->assertFalse($reachedMax, 'Remindertype with max_iterations="2" and 1 log DOES NOT have max iterations?');

    CRM_Casereminder_Util_Log::logReminderCase($reminderTypeMaxTwo['id'], CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $case['id']);
    $reachedMax = CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($reminderTypeMaxTwo, $case);
    $this->assertTrue($reachedMax, 'Remindertype with max_iterations="2" and 2 log DOES NOT have max iterations?');
  }

  public function testReminderTypeCaseSentToday() {

    // CareReminderLogCase requires an actual case reminder type, so create that now.
    $reminderType = $this->createCaseReminderType();

    // CaseReminderLogCase requires an actual case, so create that now.
    $caseSentToday = $this->createCase($this->contactIds['creator'], $this->contactIds['creator'], [
      'subject' => 'TESTING: Case SentToday',
    ]);
    $caseSentYesterday = $this->createCase($this->contactIds['creator'], $this->contactIds['creator'], [
      'subject' => 'TESTING: Case SentYesterday',
    ]);
    $caseNotSent = $this->createCase($this->contactIds['creator'], $this->contactIds['creator'], [
      'subject' => 'TESTING: Case NotSent',
    ]);

    // Log that a reminder was sent now for $caseSentToday.
    $apiParams = [
      'log_time' => $this->now->getMysqlDatetime(),
      'case_reminder_type_id' => $reminderType['id'],
      'case_id' => $caseSentToday['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_CASE_SEND,
    ];
    $created = $this->callAPISuccess('CaseReminderLogCase', 'create', $apiParams);

    $this->assertTrue(CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderType, $caseSentToday), 'Case sent today is returned?');
    $this->assertFalse(CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderType, $caseSentYesterday), 'Case sent yesterday is NOT returned?');
    $this->assertFalse(CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderType, $caseNotSent), 'Case not sent is NOT returned?');
  }

}

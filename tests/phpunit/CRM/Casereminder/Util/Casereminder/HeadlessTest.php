<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Casereminder_ExtensionUtil as E;

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
    // Set now to tomorrow
    $this->tomorrow = new DateTime('@' . strtotime('+2 days'));

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

    // Ensure there's a logged-in user.
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

    $recpientList = CRM_Casereminder_Util_Casereminder::buildRecipientList($case, $reminderType);
    $this->assertContains($this->contactIds['client'], $recpientList, 'Recipient list contains client.');
    $this->assertContains($role14ContactId, $recpientList, 'Recipient list contains "role 14" contact.');
    $this->assertNotContains($role12ContactId, $recpientList, 'Recipient list doees not contain "role 12" contact.');
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

  public function testSendCaseReminder() {
    /* This test relies on code in another extension (emailapi), which is known
     * to produce testing errors unless patched. Therefore we'll skip it if
     * the environment variable CASEREMINDER_TESTING_SKIP_EXTERNAL. For more
     * information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      $this->addWarning("CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md");
      return;
    }

    $msgTplSubjectMarker = uniqid();
    $caseSubjectMarker = uniqid();
    $reminderTypeSubjectMarker = uniqid();
    $role14Email = uniqid() . '+role14@example.com';
    $role12Email = uniqid() . '+role12@example.com';
    $clientEmail = uniqid() . '+client@example.com';
    $caseEndDate = $this->tomorrow->format('Y-m-d');

    // Create a message template
    $msgTplParams = [
      'msg_title' => 'Case Reminder 2',
      'msg_subject' => $msgTplSubjectMarker,
      'is_active' => '1',
      'is_default' => '1',
      'is_reserved' => '0',
      'is_sms' => '0',
      "msg_html" => "
        Case Subject: {CaseReminder_Case.subject}|\r\n
        Case Type: {CaseReminder_Case.case_type_id}|\r\n
        Case Start Date: {CaseReminder_Case.start_date}|\r\n
        Case End Date: {CaseReminder_Case.end_date}|\r\n
        Case Status: {CaseReminder_Case.status_id}|\r\n
        Case is in the Trash: {CaseReminder_Case.is_deleted}|\r\n
        Created Date: {CaseReminder_Case.created_date}|\r\n
        Modified Date: {CaseReminder_Case.modified_date}|\r\n
        Case ID: {CaseReminder_Case.id}|\r\n
      ",
    ];
    $msgTpl = civicrm_api3('MessageTemplate', 'create', $msgTplParams);
    $this->assertTrue(is_int($msgTpl['id']), 'New message template has integer id.');

    // create client and case
    $clientContactId = $this->individualCreate(['email' => $clientEmail]);
    $case = $this->createCase($this->contactIds['creator'], $clientContactId, [
      'case_type_id' => 1,
      'subject' => $caseSubjectMarker,
      'end_date' => $caseEndDate,
    ]);

    // Create contacts for roles.
    $role14ContactId = $this->individualCreate(['email' => $role14Email]);
    $role12ContactId = $this->individualCreate(['email' => $role12Email]);

    // Ensure there's a logged-in user.
    $this->createLoggedInUser();

    // Add contacts in case roles.
    $this->addCaseRoleContact($case['id'], $this->contactIds['client'], 12, $role12ContactId);
    $this->addCaseRoleContact($case['id'], $this->contactIds['client'], 14, $role14ContactId);

    // Create caseremindertype specifying recpient roles -1 and 14.
    $reminderTypeParams = [
      'case_type_id' => 1,
      'subject' => "$reminderTypeSubjectMarker:{CaseReminder_Case.subject}",
      'recipient_relationship_type_id' => [-1, 14],
      'msg_template_id' => $msgTpl['id'],
      'from_email_address' => '"Batman"<b@superfriends.example.com>',
    ];
    $reminderType = $this->createCaseReminderType($reminderTypeParams);

    $sendingParams = CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams($case, $reminderType);
    $recipientCids = CRM_Casereminder_Util_Casereminder::buildRecipientList($case, $reminderType);

    // Get the latest case data for comparison (sending an email will modify the case, after
    // tokens are processed, so we want these values now.)
    $latestCaseValues = civicrm_api3('case', 'getSingle', ['id' => $case['id']]);

    /* Send case reminders.
     * NOTE: This will fail if we don't properly alter lines in other people's code.
     * See TESTING.md.
     */
    CRM_Casereminder_Util_Casereminder::sendCaseReminder($case['id'], $recipientCids, $sendingParams);

    $mailingGetDefaultParams = [
      'sequential' => 1,
      'is_archived' => 1,
      'subject' => "{$reminderTypeSubjectMarker}:{$caseSubjectMarker}",
      'options' => ['limit' => 0],
    ];

    // Test role 12 email message.
    $mailingGetParams = $mailingGetDefaultParams;
    $mailingGetParams['body_html'] = ['LIKE' => "%{$role12Email}%"];
    $mailingGet = civicrm_api3('Mailing', 'get', $mailingGetParams);
    $this->assertEquals(0, $mailingGet['count'], 'No mailing sent to role12.');

    // Test role 14 email message.
    $mailingGetParams = $mailingGetDefaultParams;
    $mailingGetParams['body_html'] = ['LIKE' => "%{$role14Email}%"];
    $mailingGet = civicrm_api3('Mailing', 'get', $mailingGetParams);
    $this->assertEquals(1, $mailingGet['count'], 'One mailing sent to role14.');

    // Test client email message.
    $mailingGetParams = $mailingGetDefaultParams;
    $mailingGetParams['body_html'] = ['LIKE' => "%{$clientEmail}%"];
    $mailingGet = civicrm_api3('Mailing', 'get', $mailingGetParams);
    $this->assertEquals(1, $mailingGet['count'], 'One mailing sent to client.');

    // Prepare to test token values in mailing body.
    $mailing = reset($mailingGet['values']);
    $mailingHtml = $mailing['body_html'];

    $this->assertStringContainsString("Case Type: Housing Support|", $mailingHtml, 'Mailing html tokens show correct case type.');
    $this->assertStringContainsString("Case Status: Ongoing|", $mailingHtml, 'Mailing html tokens show correct case status.');
    $this->assertStringContainsString("Case Subject: {$latestCaseValues['subject']}|", $mailingHtml, 'Mailing html tokens show correct case subject.');
    $this->assertStringContainsString("Case Start Date: {$latestCaseValues['start_date']}|", $mailingHtml, 'Mailing html tokens show correct case start date.');
    $this->assertStringContainsString("Case End Date: {$latestCaseValues['end_date']}|", $mailingHtml, 'Mailing html tokens show correct case end date.');
    $this->assertStringContainsString("Created Date: {$latestCaseValues['created_date']}|", $mailingHtml, 'Mailing html tokens show correct case created date.');
    $this->assertStringContainsString("Modified Date: {$latestCaseValues['modified_date']}", $mailingHtml, 'Mailing html tokens show correct case modified date.');
  }

  public function testReminderTypeCompletedToday() {
    // Active
    $reminderType = $this->createCaseReminderType();
    $completedTodayFalse = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($reminderType);
    $this->assertFalse($completedTodayFalse, 'Freshly created reminder type is NOT completed today');

    $apiParams = [
      'log_time' => $this->now->getMysqlDatetime(),
      'case_reminder_type_id' => $reminderType['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE,
    ];

    $created = $this->callAPISuccess('CaseReminderLogType', 'create', $apiParams);

    $completedTodayTrue = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($reminderType);
    $this->assertTrue($completedTodayTrue, '"Completed"-logged reminder type IS completed today');

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

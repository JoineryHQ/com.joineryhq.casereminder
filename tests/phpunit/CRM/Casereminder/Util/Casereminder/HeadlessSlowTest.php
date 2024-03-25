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
    $msgTplBodyMarker = uniqid();
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
        msgTplBodyMarker:{$msgTplBodyMarker}|\r\n
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
    $recipientRolePerCid = CRM_Casereminder_Util_Casereminder::buildRecipientList($case, $reminderType);

    // Get the latest case data for comparison (sending an email will modify the case, after
    // tokens are processed, so we want these values now.)
    $latestCaseValues = civicrm_api3('case', 'getSingle', ['id' => $case['id']]);

    // Verify that no log entry exists for this in CaseReminderLogCase.
    // Later we'll verify that there's exactly 1.
    $caseReminderLogCaseGetCount = $this->callAPISuccess('CaseReminderLogCase', 'getcount', [
      'case_reminder_type_id' => $reminderType['id'],
      'case_id' => $case['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_CASE_SEND,
    ]);
    $this->assertEquals(0, $caseReminderLogCaseGetCount, 'Before sending, no CaseReminderLogCase log entry found for this case/remindertype?');

    /* Send case reminders.
     * NOTE: This will fail if we don't properly alter lines in other people's code.
     * See TESTING.md.
     */
    CRM_Casereminder_Util_Casereminder::sendCaseReminder($case['id'], $reminderType['id'], $recipientRolePerCid, $sendingParams);

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

    // Prepare to test mailing body.
    $mailing = reset($mailingGet['values']);
    $mailingHtml = $mailing['body_html'];
    $this->assertStringContainsString("msgTplBodyMarker:{$msgTplBodyMarker}|", $mailingHtml, 'Mailing html contains $msgTplBodyMarker?');
    $this->assertStringContainsString("Case Subject: {$caseSubjectMarker}|", $mailingHtml, 'Mailing html was processed for tokens (case.subject token matched)?');

    // Verify there's exactly one log entry for this in CaseReminderLogCase.
    $caseReminderLogCaseGetCount = $this->callAPISuccess('CaseReminderLogCase', 'getcount', [
      'case_reminder_type_id' => $reminderType['id'],
      'case_id' => $case['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_CASE_SEND,
    ]);
    $this->assertEquals(1, $caseReminderLogCaseGetCount, 'After sending, exactly 1 CaseReminderLogCase log entry found for this case/remindertype?');
  }

}

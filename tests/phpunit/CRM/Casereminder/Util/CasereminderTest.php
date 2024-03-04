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
class CRM_Casereminder_Util_CasereminderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  private $contactIds = [];
  private $caseReminderTypeIds = [];
  private $now;
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
    parent::setUp();

    // Set now to tomorrow
    $this->now = CRM_Casereminder_Util_Time::singleton(strtotime('+1 day'));
    $this->tomorrow = new DateTime('@' . strtotime('+2 days'));

    $this->contactIds['client'] = $this->individualCreate();
    $this->contactIds['creator']  = $this->individualCreate();

    // Create a few reminder types
    // Active
    $caseReminderTypeApiParams = [
      'case_type_id' => 1,
      'case_status_id' => [1, 2],
      'msg_template_id' => 1,
      'recipient_relationship_type_id' => [-1, 14],
      'from_email_address' => '"Micky Mouse"<mickey@mouse.example.com>',
      'subject' => 'Subject:active',
      'dow' => $this->now->getDayOfWeek(),
      'max_iterations' => '1000',
      'is_active' => 1,
    ];
    $createCaseReminderType = $this->callAPISuccess('CaseReminderType', 'create', $caseReminderTypeApiParams);
    $this->caseReminderTypeIds['inactive'] = $createCaseReminderType['id'];

    // Inactive
    $caseReminderTypeApiParams = [
      'case_type_id' => 1,
      'case_status_id' => [1, 2],
      'msg_template_id' => 1,
      'recipient_relationship_type_id' => [-1, 14],
      'from_email_address' => '"Micky Mouse"<mickey@mouse.example.com>',
      'subject' => 'Subject:inactive',
      'dow' => $this->now->getDayOfWeek(),
      'max_iterations' => '1000',
      'is_active' => 0,
    ];
    $createCaseReminderType = $this->callAPISuccess('CaseReminderType', 'create', $caseReminderTypeApiParams);
    $this->caseReminderTypeIds['active'] = $createCaseReminderType['id'];

    // Tomorrow
    $caseReminderTypeApiParams = [
      'case_type_id' => 1,
      'case_status_id' => [1, 2],
      'msg_template_id' => 1,
      'recipient_relationship_type_id' => [-1, 14],
      'from_email_address' => '"Micky Mouse"<mickey@mouse.example.com>',
      'subject' => 'Subject:tomorrow',
      'dow' => $this->tomorrow->format('l'),
      'max_iterations' => '1000',
      'is_active' => 1,
    ];
    $createCaseReminderType = $this->callAPISuccess('CaseReminderType', 'create', $caseReminderTypeApiParams);
    $this->caseReminderTypeIds['active'] = $createCaseReminderType['id'];

    // Create a few cases
    $caseApiParams = [
      'contact_id' => $this->contactIds['client'],
      'creator_id' => $this->contactIds['creator'],
      'case_type_id' => 'housing_support',
      'subject' => "TESTING:housing_support",
    ];
    $createCase = $this->callAPISuccess('Case', 'create', $caseApiParams);

    $caseApiParams = [
      'contact_id' => $this->contactIds['client'],
      'creator_id' => $this->contactIds['creator'],
      'case_type_id' => 'adult_day_care_referral',
      'subject' => "TESTING:adult_day_care_referral",
    ];
    $createCase = $this->callAPISuccess('Case', 'create', $caseApiParams);
  }

  public function tearDown():void {
    parent::tearDown();
  }

  public function testGetNowReminderTypesIsCorrect() {
    $reminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(1, count($reminderTypes), 'One reminder type found.');
    $reminderType = reset($reminderTypes);
    $this->assertEquals('Subject:active', $reminderType['subject'], 'Reminder type subject is correct');
  }

  public function testGetReminderTypeCases() {
    $reminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $reminderType = reset($reminderTypes);
    $cases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($reminderType);
    $this->assertEquals(1, count($cases), 'One case found for reminder type.');
    $case = reset($cases);
    $this->assertEquals('TESTING:housing_support', $case['subject'], 'Case subject is correct');
  }

  public function sendCaseReminder($case, $reminderType) {

  }

  public function buildRecipientList($case, $reminderType) {

  }

  public function reminderTypeNeededNow($reminderType) {

  }

  public function reminderTypeCaseNeededNow($reminderType, $case) {

  }

  public function splitFromEmail($fromEmail) {

  }

  /**
   * Example: Test that a version is returned.
   */
  public function testWellFormedVersion():void {
    $this->assertNotEmpty(E::SHORT_NAME);
    $this->assertRegExp('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a fake CMS.
   */
  public function testWellFormedUF():void {
    $this->assertEquals('UnitTests', CIVICRM_UF);
  }

}

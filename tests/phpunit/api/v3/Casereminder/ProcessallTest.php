<?php

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Casereminder.Processall API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Casereminder_ProcessallTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use CRM_CasereminderTestTrait;

  private $contactIds = [];
  private $reminderTypes = [];
  private $cases = [];
  private $tomorrow;

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

  public static function setUpBeforeClass(): void {
    self::stupidCleanup();
  }

  public static function tearDownAfterClass(): void {
    self::stupidCleanup();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp(): void {
    /* All tests in this class require CASEREMINDER_TESTING_SKIP_EXTERNAL.
     * So if it's not enabled, skip the expensive setup.
     * For more information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      return;
    }

    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->stupidCleanup();
  }

  public function stupidSetup() {
    // See also: self::stupidCleanup().
    self::stupidCleanup();
    self::stupidCleanupCheck();

    $this->setupCasereminderTests();

    $this->tomorrow = new DateTime('+2 days');

    civicrm_api3('setting', 'create', ['mailing_backend' => ['outBound_option' => 5]]);
    civicrm_api3('Extension', 'enable', [
      'keys' => "org.civicoop.emailapi",
    ]);

    $this->contactIds['client'] = $this->individualCreate(['email' => 'client@example.com']);
    $this->contactIds['creator']  = $this->individualCreate(['email' => 'creator@example.com']);

    $this->reminderTypes['today1'] = $this->createCaseReminderType([
      'subject' => 'RT today1',
    ]);
    $this->reminderTypes['today2'] = $this->createCaseReminderType([
      'subject' => 'RT today2',
    ]);
    $this->reminderTypes['tomorrow'] = $this->createCaseReminderType([
      'subject' => 'RT tomorrow',
      'dow' => $this->tomorrow->format('l'),
    ]);

    $this->cases[1] = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], []);
    $this->cases[2] = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], []);

  }

  public static function stupidCleanup() {
    // Something in these tests is breaking transaction rollbacks, so we'll just
    // brute-force cleanup all of our tables. This probably still leaves dirty
    // data in places like civicrm_contact, but we're not testing there.
    //
    // The problem is pretty stubborn. Test data is still laying around
    // event after cleaning it up manually in setUp() and in tearDown().
    // So I'm going to try calling this stupid cleanup at the top of every test.
    //
    //
    // What's breaking the rollbacks? Some quick trial-and-error indicates that
    // it's something in the Queue runner (CRM_Queue_Runner::runNext()).
    // Maybe we'll figure that out later.
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_job');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_job_recipient');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_job_recipient_error');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_log_case');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_log_type');
    CRM_Core_DAO::executeQuery('DELETE FROM  civicrm_case_reminder_type');
  }

  public static function stupidCleanupCheck() {
    // This is stupid. Anyway, uncomment the return line below if you want to get
    // output in the terminal for the status of this cleanup.
    return;
    $query = "
      select count(*) as cnt, 'civicrm_case_reminder_job' as tablename from civicrm_case_reminder_job
      UNION
      select count(*) as cnt, 'civicrm_case_reminder_job_recipient' as tablename from civicrm_case_reminder_job_recipient
      UNION
      select count(*) as cnt, 'civicrm_case_reminder_job_recipient_error' as tablename from civicrm_case_reminder_job_recipient_error
      UNION
      select count(*) as cnt, 'civicrm_case_reminder_log_case' as tablename from civicrm_case_reminder_log_case
      UNION
      select count(*) as cnt, 'civicrm_case_reminder_log_type' as tablename from civicrm_case_reminder_log_type
      UNION
      select count(*) as cnt, 'civicrm_case_reminder_type' as tablename from civicrm_case_reminder_type
    ";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      var_dump("{$dao->cnt} : {$dao->tablename}");
    }
  }

  public function testProcessAllOverTime() {
    /* This test calls api casereminder.process all, which means that it
     * relies on code in another extension (emailapi), which is known
     * to produce testing errors unless patched. Therefore we'll skip it if
     * the environment variable CASEREMINDER_TESTING_SKIP_EXTERNAL. For more
     * information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      $this->addWarning("CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md");
      return;
    }
    $this->stupidSetup();

    $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(2, count($nowReminderTypes), 'After setup(): Two nowReminderTypes found?');
    $nowReminderTypeIds = array_keys($nowReminderTypes);
    $this->assertContains($this->reminderTypes['today1']['id'], $nowReminderTypeIds, 'After setup(): RT1 is among nowReminderTypes?');
    $this->assertContains($this->reminderTypes['today2']['id'], $nowReminderTypeIds, 'After setup(): RT2 is among nowReminderTypes?');
    $this->assertNotContains($this->reminderTypes['tomorrow']['id'], $nowReminderTypeIds, 'After setup(): RT"tomorrow" is NOT among nowReminderTypes?');

    $caseReminderLogTypeCount = $this->callAPISuccess('CaseReminderLogType', 'getcount', []);
    $this->assertEquals(0, $caseReminderLogTypeCount, 'After setup: 0 CaseReminderLogType entries found?');
    $caseReminderLogCaseCount = $this->callAPISuccess('CaseReminderLogCase', 'getcount', []);
    $this->assertEquals(0, $caseReminderLogCaseCount, 'After setup: 0 CaseReminderLogCase entries found?');

    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');

    // Two reminderTypes were processed, so should be 2 'BEGIN' log entries.
    $caseReminderLogTypeBeginCount = $this->callAPISuccess('CaseReminderLogType', 'getcount', ['action' => CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_BEGIN]);
    $this->assertEquals(2, $caseReminderLogTypeBeginCount, 'After processall: 2 CaseReminderLogType "BEGIN" entries found?');
    // Also should be 2 'COMPLETE' log entries.
    $caseReminderLogTypeCompleteCount = $this->callAPISuccess('CaseReminderLogType', 'getcount', ['action' => CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE]);
    $this->assertEquals(2, $caseReminderLogTypeCompleteCount, 'After processall: 2 CaseReminderLogType "COMPLETE" entries found?');

    // 2 reminderTypes were processed, for 2 cases, so should be 4 case/type log entries (2 * 2 = 4).
    $caseReminderLogCaseCount = $this->callAPISuccess('CaseReminderLogCase', 'getcount', []);
    $this->assertEquals(4, $caseReminderLogCaseCount, 'After processall: 4 CaseReminderLogCase entries found?');

    // Test correct values for 'RT completed today' for all 3 RTs.
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today1']);
    $this->assertTrue($completed, 'After processall: RT"today1" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today2']);
    $this->assertTrue($completed, 'After processall: RT"today2" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['tomorrow']);
    $this->assertFalse($completed, 'After processall: RT"tomorrow" NOT completed today?');

    // Test correct values for 'rt case sent (or not sent) today' for all RTs/Cases
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today1'], $this->cases[1]);
    $this->assertTrue($sentToday, 'After processall: RT"today1", case 1, sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today1'], $this->cases[2]);
    $this->assertTrue($sentToday, 'After processall: RT"today1", case 2, sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today2'], $this->cases[1]);
    $this->assertTrue($sentToday, 'After processall: RT"today2", case 1, sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today2'], $this->cases[2]);
    $this->assertTrue($sentToday, 'After processall: RT"today2", case 2, sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['tomorrow'], $this->cases[1]);
    $this->assertFalse($sentToday, 'After processall: RT"tomorrow", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['tomorrow'], $this->cases[2]);
    $this->assertFalse($sentToday, 'After processall: RT"tomorrow", case 2, NOT sent today?');

    ////////////////////////////////////////////////////////////////////////////////
    // Increment $now by 1 day (i.e., test behavior on next calendar day).
    $this->now->modify('+1 day');

    // Test correct values for 'RT completed today' for all 3 RTs. These should all be 'no', because it's a new day now.
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today1']);
    $this->assertFalse($completed, 'On day 2:  RT"today1" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today2']);
    $this->assertFalse($completed, 'On day 2:  RT"today2" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['tomorrow']);
    $this->assertFalse($completed, 'On day 2:  RT"tomorrow" NOT completed today?');

    // Test correct values for 'rt case NOT sent today' for all RTs/Cases. These should all be 'no', because it's a new day now.
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today1'], $this->cases[1]);
    $this->assertFalse($sentToday, 'On day 2:  RT"today1", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today1'], $this->cases[2]);
    $this->assertFalse($sentToday, 'On day 2:  RT"today1", case 2, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today2'], $this->cases[1]);
    $this->assertFalse($sentToday, 'On day 2:  RT"today2", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['today2'], $this->cases[2]);
    $this->assertFalse($sentToday, 'On day 2:  RT"today2", case 2, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['tomorrow'], $this->cases[1]);
    $this->assertFalse($sentToday, 'On day 2:  RT"tomorrow", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($this->reminderTypes['tomorrow'], $this->cases[2]);
    $this->assertFalse($sentToday, 'On day 2:  RT"tomorrow", case 2, NOT sent today?');

    // Since now->day-of-week is one day after original starting time, the "nowReminderTypes" should NOT include 'today1' and 'today2', but SHOULD include 'tomorrow'.
    $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(1, count($nowReminderTypes), 'After setup(): Two nowReminderTypes found?');
    $nowReminderTypeIds = array_keys($nowReminderTypes);
    $this->assertNotContains($this->reminderTypes['today1']['id'], $nowReminderTypeIds, 'After setup(): RT1 is among nowReminderTypes?');
    $this->assertNotContains($this->reminderTypes['today2']['id'], $nowReminderTypeIds, 'After setup(): RT2 is among nowReminderTypes?');
    $this->assertContains($this->reminderTypes['tomorrow']['id'], $nowReminderTypeIds, 'After setup(): RT"tomorrow" is NOT among nowReminderTypes?');

    ////////////////////////////////////////////////////////////////////////////////
    // Start the clock over and increment 7 days (i.e., one week in future from original starting time).
    $this->now->revert();
    $this->now->modify('+7 day');

    // Since now->day-of-week is same as original starting time, the "nowReminderTypes" should again include 'today1' and 'today2', but not 'tomorrow'.
    $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(2, count($nowReminderTypes), 'On day 7: Two nowReminderTypes found?');
    $nowReminderTypeIds = array_keys($nowReminderTypes);
    $this->assertContains($this->reminderTypes['today1']['id'], $nowReminderTypeIds, 'On day 7: RT1 is among nowReminderTypes?');
    $this->assertContains($this->reminderTypes['today2']['id'], $nowReminderTypeIds, 'On day 7: RT2 is among nowReminderTypes?');
    $this->assertNotContains($this->reminderTypes['tomorrow']['id'], $nowReminderTypeIds, 'On day 7: RT"tomorrow" is NOT among nowReminderTypes?');
  }

  /**
   *
   */
  public function testProcessAllHonorsMaxIterations() {
    /* This test calls api casereminder.process all, which means that it
     * relies on code in another extension (emailapi), which is known
     * to produce testing errors unless patched. Therefore we'll skip it if
     * the environment variable CASEREMINDER_TESTING_SKIP_EXTERNAL. For more
     * information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      $this->addWarning("CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md");
      return;
    }
    $this->stupidSetup();

    // Create a reminderType with max_iterations=1. This should cause reminders
    // to be sent for all cases (case1 and case2), but only the first time.
    $reminderTypeMax1 = $this->createCaseReminderType([
      'subject' => 'RT max_iterations 1',
      'max_iterations' => 1,
    ]);

    // Call the processall api.
    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');

    // Test correct values for 'rt case sent (or not sent) today' for Cases 1 and 2, with rt"max1"
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[1]);
    $this->assertTrue($sentToday, 'After processall: RT"Max1", case 1, sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[2]);
    $this->assertTrue($sentToday, 'After processall: RT"Max1", case 2, sent today?');

    ////////////////////////////////////////////////////////////////////////////////
    // Increment 7 days (i.e., one week in future from original starting time).
    $this->now->modify('+7 day');

    // Create another case (case3). This case should get a reminder today for RT"max1", because
    // so far it has not received any reminders (it's a brand new case).
    $case3 = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], []);

    // Call the processall api.
    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');

    // Test correct values for 'rt case NOT sent today' for Cases 1 and 2, with rt"max1". These should have
    // both reached max_iterations on Day 1, so now on Day 7 their reminders should not be sent.
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[1]);
    $this->assertFalse($sentToday, 'After processall (day 7): RT"Max1", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[2]);
    $this->assertFalse($sentToday, 'After processall (day 7): RT"Max1", case 2, NOT sent today?');

    // Test 'rt case sent today' for Case 3 with rt"max1". This should have a sent reminder,
    // because it's a new case today, so it could not have reached max_iterations yet.
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $case3);
    $this->assertTrue($sentToday, 'After processall (day 7): RT"Max1", case 3, sent today?');

    ////////////////////////////////////////////////////////////////////////////////
    // Start the clock over and increment 14 days (i.e., two weeks in future from original starting time).
    $this->now->revert();
    $this->now->modify('+14 day');

    // Call the processall api.
    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');

    // By Day 7, all cases (1, 2 and 3) should have reached max_iterations, so today (Day 14),
    // none of them should have reminders sent.
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[1]);
    $this->assertFalse($sentToday, 'After processall (day 14): RT"Max1", case 1, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $this->cases[2]);
    $this->assertFalse($sentToday, 'After processall (day 14): RT"Max1", case 2, NOT sent today?');
    $sentToday = CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($reminderTypeMax1, $case3);
    $this->assertFalse($sentToday, 'After processall (day 14): RT"Max1", case 3, sent today?');

  }

  public function testProcessAllReturnValuesBrief() {
    /* This test calls api casereminder.process all, which means that it
     * relies on code in another extension (emailapi), which is known
     * to produce testing errors unless patched. Therefore we'll skip it if
     * the environment variable CASEREMINDER_TESTING_SKIP_EXTERNAL. For more
     * information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      $this->addWarning("CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md");
      return;
    }
    $this->stupidSetup();

    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');
    $expected = [
      'totalReminderTypesProcessed' => 2,
      'totalCasesProcessed' => 4,
      'totalReminderTypesProcessed' => 2,
      'totalRecipientsEnqueued' => 4,
      'totalRemindersSent' => 0,
      'totalRemindersSent-info' => NULL,
    ];
    $this->assertEquals($expected, $apiResult['values'], 'API brief results are correct?');
  }

  public function testProcessAllReturnValuesVerbose() {
    /* This test calls api casereminder.process all, which means that it
     * relies on code in another extension (emailapi), which is known
     * to produce testing errors unless patched. Therefore we'll skip it if
     * the environment variable CASEREMINDER_TESTING_SKIP_EXTERNAL. For more
     * information, see TESTING.md.
     */
    if (!getenv('CASEREMINDER_TESTING_COVER_EXTERNAL')) {
      $this->addWarning("CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md");
      return;
    }
    $this->stupidSetup();

    $apiResult = $this->callAPISuccess('Casereminder', 'processAll', ['verbose' => TRUE]);

    //  NOTE: $restult['values'] should look something like the following, though IDs will differ.
    //  $restult['values'] = [
    //    'reminderTypesProcessed' => [
    //      'count' => 2,
    //      'ids' => [
    //        0 => '400',
    //        1 => '401',
    //      ],
    //    ],
    //    'casesPerReminderType' => [
    //      400 => [
    //        'caseIds' => [
    //          0 => '282',
    //          1 => '283',
    //        ],
    //        'count' => 2,
    //      ],
    //      401 => [
    //        'caseIds' => [
    //          0 => '282',
    //          1 => '283',
    //        ],
    //        'count' => 2,
    //      ],
    //    ],
    //    'summary' => [
    //      'reminderTypesProcessed' => 2,
    //      'totalCasesProcessed' => 4,
    //      'totalRemindersProcessed' => 4,
    //    ],
    //  ];
    //  HOWEVER, the IDs are unpredictable (even with commit rollbacks, autoincrement `id` columns
    //  will continue to increment).
    //
    //  So we'll have to test for relevant values (not keys) and counts.

    $values = $apiResult['values'];
    $expectedSummary = [
      'totalReminderTypesProcessed' => 2,
      'totalCasesProcessed' => 4,
      'totalRecipientsEnqueued' => 4,
      'totalRemindersSent' => 0,
      'totalRemindersSent-info' => NULL,
    ];

    $this->assertEquals($expectedSummary, $values['summary'], 'API verbose "summary" results are correct?');
    $this->assertEquals(2, $values['reminderTypesProcessed']['count'], 'API results correct for reminderTypesProcessed:count?');
    $this->assertContains($this->reminderTypes['today1']['id'], $values['reminderTypesProcessed']['ids'], 'API results contain RT"today1" in reminderTypesProcessed:ids?');
    $this->assertContains($this->reminderTypes['today2']['id'], $values['reminderTypesProcessed']['ids'], 'API results contain RT"today2" in reminderTypesProcessed:ids?');

    $this->assertEquals(2, count($values['casesPerReminderType']), 'API results show 2 RTs in casesPerReminderType?');

    $this->assertEquals(2, $values['casesPerReminderType'][$this->reminderTypes['today1']['id']]['count'], 'casesPerReminderType[RT"today1"][count] is 2?');
    $this->assertEquals(2, count($values['casesPerReminderType'][$this->reminderTypes['today1']['id']]), 'casesPerReminderType shows 2 cases for RT"today1"?');
    $this->assertContains($this->cases[1]['id'], $values['casesPerReminderType'][$this->reminderTypes['today1']['id']]['caseIds'], 'casesPerReminderType for RT"today1" contains case1?');
    $this->assertContains($this->cases[2]['id'], $values['casesPerReminderType'][$this->reminderTypes['today1']['id']]['caseIds'], 'casesPerReminderType for RT"today1" contains case2?');

    $this->assertEquals(2, $values['casesPerReminderType'][$this->reminderTypes['today2']['id']]['count'], 'casesPerReminderType[RT"today2"][count] is 2?');
    $this->assertEquals(2, count($values['casesPerReminderType'][$this->reminderTypes['today2']['id']]), 'casesPerReminderType shows 2 cases for RT"today2"?');
    $this->assertContains($this->cases[1]['id'], $values['casesPerReminderType'][$this->reminderTypes['today2']['id']]['caseIds'], 'casesPerReminderType for RT"today2" contains case1?');
    $this->assertContains($this->cases[2]['id'], $values['casesPerReminderType'][$this->reminderTypes['today2']['id']]['caseIds'], 'casesPerReminderType for RT"today2" contains case2?');

  }

}

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

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   *
   */
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

    ////////////////////////////////////////////////////////////////////////////////
    // Increment $now by 1 day (i.e., test behavior on next calendar day).
    $this->now->modify('+1 day');

    // Test correct values for 'RT completed today' for all 3 RTs. These should all be 'no', because it's a new day now.
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today1']);
    $this->assertFalse($completed, 'After processall: RT"today1" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['today2']);
    $this->assertFalse($completed, 'After processall: RT"today2" completed today?');
    $completed = CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($this->reminderTypes['tomorrow']);
    $this->assertFalse($completed, 'After processall: RT"tomorrow" NOT completed today?');

    // Since now->day-of-week is one day after original starting time, the "nowReminderTypes" should NOT include 'today1' and 'today2', but SHOULD include 'tomorrow'.
    $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(1, count($nowReminderTypes), 'After setup(): Two nowReminderTypes found?');
    $nowReminderTypeIds = array_keys($nowReminderTypes);
    $this->assertNotContains($this->reminderTypes['today1']['id'], $nowReminderTypeIds, 'After setup(): RT1 is among nowReminderTypes?');
    $this->assertNotContains($this->reminderTypes['today2']['id'], $nowReminderTypeIds, 'After setup(): RT2 is among nowReminderTypes?');
    $this->assertContains($this->reminderTypes['tomorrow']['id'], $nowReminderTypeIds, 'After setup(): RT"tomorrow" is NOT among nowReminderTypes?');

    ////////////////////////////////////////////////////////////////////////////////
    // Start the clock over and increment 7 days (i.e., one week in future from original starting time).
    $this->now->reset();
    $this->now->modify('+7 day');

    // Since now->day-of-week is same as original starting time, the "nowReminderTypes" should again include 'today1' and 'today2', but not 'tomorrow'.
    $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
    $this->assertEquals(2, count($nowReminderTypes), 'After setup(): Two nowReminderTypes found?');
    $nowReminderTypeIds = array_keys($nowReminderTypes);
    $this->assertContains($this->reminderTypes['today1']['id'], $nowReminderTypeIds, 'After setup(): RT1 is among nowReminderTypes?');
    $this->assertContains($this->reminderTypes['today2']['id'], $nowReminderTypeIds, 'After setup(): RT2 is among nowReminderTypes?');
    $this->assertNotContains($this->reminderTypes['tomorrow']['id'], $nowReminderTypeIds, 'After setup(): RT"tomorrow" is NOT among nowReminderTypes?');
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

    $apiResult = $this->callAPISuccess('Casereminder', 'processAll');
    $expected = [
      'reminderTypesProcessed' => 2,
      'totalCasesProcessed' => 4,
      'totalRemindersProcessed' => 4,
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

    $apiResult = $this->callAPISuccess('Casereminder', 'processAll', ['verbose' => TRUE]);
    //  NOTE: $restult['values'] SHOULD look something like the following:
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
      'reminderTypesProcessed' => 2,
      'totalCasesProcessed' => 4,
      'totalRemindersProcessed' => 4,
    ];
    $this->assertEquals($expectedSummary, $values['summary'], 'API verbose "summary" results are correct?');
    $this->assertEquals(2, $values['reminderTypesProcessed']['count'], '');
    $this->assertEquals(2, count($values['reminderTypesProcessed']['ids']), '');

    $this->assertEquals(2, count($values['casesPerReminderType']), '');
    $i = 0;
    foreach ($values['casesPerReminderType'] as $reminderTypeCases) {
      $this->assertEquals(2, count($reminderTypeCases['caseIds']), "casesPerReminderType[{$i}th element]: count of caseIds is correct?");
      $this->assertEquals(2, $reminderTypeCases['count'], "casesPerReminderType[{$i}th element]: 'count' value is correct?");
      $i++;
    }

  }

}

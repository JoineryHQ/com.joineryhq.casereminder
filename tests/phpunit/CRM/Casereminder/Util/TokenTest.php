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
class CRM_Casereminder_Util_TokenTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use CRM_CasereminderTestTrait;

  private $contactIds = [];

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
  }

  public function tearDown():void {
    parent::tearDown();
  }

  /**
   * Test that token values are correctly calculated.
   */
  public function testGetTokenValuesNow():void {
    $caseSubjectMarker = uniqid();
    $caseEndDate = $this->tomorrow->format('Y-m-d');

    // create client and case
    $case = $this->createCase($this->contactIds['creator'], $this->contactIds['client'], [
      'case_type_id' => 1,
      'subject' => $caseSubjectMarker,
      'end_date' => $caseEndDate,
    ]);
    
    // Hardcoded list of token names. Tokens should support custom fields, too,
    // but we're not testing those at present.
    $myTokenNames = [
      'id',
      'case_type_id',
      'created_date',
      'end_date',
      'is_deleted',
      'modified_date',
      'start_date',
      'status_id',
      'subject',
    ];
    // Case may have been modified, so get latest case values (including modified_date, which seems volatile).
    $case = civicrm_api3('case', 'getSingle', ['id' => $case['id'], 'return' => $myTokenNames]);
    
    // Set token environment.
    CRM_Casereminder_Util_Token::setTokenEnvCaseId($case['id']);
    // Get token values.
    $tokenValuesNow = CRM_Casereminder_Util_Token::getTokenValuesNow($myTokenNames);
    // Clear token environment.
    CRM_Casereminder_Util_Token::setTokenEnvCaseId(NULL);
    // Consider that tokens will be formatted as human-readable, so prep a lsit
    // of human-readable token values for comparison.
    $caseFormattedValues = [
      'case_type_id' => E::ts('Housing Support'),
      'status_id' => E::ts('Ongoing'),
      'is_deleted' => E::ts('No'),
      'created_date' => CRM_Utils_Date::customFormat($case['created_date']),
      'modified_date' => CRM_Utils_Date::customFormat($case['modified_date']),
    ];
    $expectedCaseTokenValues = array_merge($case, $caseFormattedValues);
    
    // Ensure token values are as expected.
    $this->assertEquals($expectedCaseTokenValues, $tokenValuesNow);
  }

}

<?php

trait CRM_CasereminderTestTrait {

  private CRM_Casereminder_Util_Time $now;

  protected $defaultCaseReminderTypeParams = [
    'case_type_id' => 'housing_support',
    'case_status_id' => [1, 2],
    'msg_template_id' => 1,
    'recipient_relationship_type_id' => [-1, 14],
    'from_email_address' => '"Mickey Mouse"<mickey@mouse.example.com>',
    'subject' => 'Test subject',
    'dow' => 'monday',
    'max_iterations' => '',
    'is_active' => 1,
  ];

  protected $defaultCaseParams = [
    'contact_id' => NULL,
    'creator_id' => NULL,
    'case_type_id' => 'housing_support',
    'subject' => "TESTING",
    // status_id 1 = 'ongoing'
    'status_id' => 1,
  ];

  protected function setupCasereminderTests() {
    // Define 'now' as tomorrow, to ensure we're not testing for system "now".
    $this->now = CRM_Casereminder_Util_Time::singleton('+1 day');
    // Some tests may have incremented $now. Reinitialize '+1 day' from real current time.
    // We could also revert() to original initialized time, but reinitializing
    // may help reduce the (vanishingly small) potential for problems as the
    // real clock proceeds during tests, getting progressively further from the
    // original value of $now.
    $this->now->reinitialize('+1 day');

    // case is not enabled by default
    $enableResult = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->assertTrue($enableResult, 'Cannot enable CiviCase in line ' . __LINE__);
  }

  protected function createCaseReminderType(array $params = []) {
    if (empty($params['dow'])) {
      $params['dow'] = $this->now->getDayOfWeek();
    }
    $apiParams = array_merge($this->defaultCaseReminderTypeParams, $params);
    $created = $this->callAPISuccess('CaseReminderType', 'create', $apiParams);
    $this->assertTrue(is_numeric($created['id']));
    return $created['values'][$created['id']];
  }

  protected function createCase(int $creatorId, int $contactId, array $params = []) {
    $apiParams = array_merge($this->defaultCaseParams, $params);
    $apiParams['creator_id'] = $creatorId;
    $apiParams['contact_id'] = $contactId;
    $created = $this->callAPISuccess('Case', 'create', $apiParams);
    $this->assertTrue(is_numeric($created['id']));
    return $created['values'][$created['id']];
  }

  protected function addCaseRoleContact (int $caseId, int $caseContactId, int $relationshipTypeId, int $contactId) {
    // Seems unwise, but do it anyway: set vars directly in REQUEST.
    $_REQUEST['case_id'] = $caseId;
    $_REQUEST['contact_id'] = $caseContactId;
    $_REQUEST['rel_type'] = $relationshipTypeId . '_b_a';
    $_REQUEST['rel_contact'] = $contactId;
    $_REQUEST['is_unit_test'] = 1;

    CRM_Contact_Page_AJAX::relationship();
  }

}

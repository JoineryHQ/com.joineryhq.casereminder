<?php
use CRM_Casereminder_ExtensionUtil as E;

/**
 * CaseReminderLogCase.create API specification (optional).
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_case_reminder_log_case_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * CaseReminderLogCase.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_log_case_create($params) {
  if (empty($params['log_time'])) {
    $now = CRM_Casereminder_Util_Time::singleton();
    $params['log_time'] = $now->getMysqlDatetime();
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'CaseReminderLogCase');
}

/**
 * CaseReminderLogCase.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_log_case_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * CaseReminderLogCase.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_log_case_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'CaseReminderLogCase');
}

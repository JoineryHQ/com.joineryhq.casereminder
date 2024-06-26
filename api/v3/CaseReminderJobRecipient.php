<?php
use CRM_Casereminder_ExtensionUtil as E;

/**
 * CaseReminderJobRecipient.create API specification (optional).
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_case_reminder_job_recipient_create_spec(&$spec) {
  $spec['job_id']['api.required'] = 1;
  $spec['case_id']['api.required'] = 1;
  $spec['contact_id']['api.required'] = 1;
}

/**
 * CaseReminderJobRecipient.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_job_recipient_create($params) {
  if (!empty($params['status']) && empty($params['status_time'])) {
    $params['status_time'] = CRM_Utils_Date::currentDBDate();
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'CaseReminderJobRecipient');
}

/**
 * CaseReminderJobRecipient.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_job_recipient_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * CaseReminderJobRecipient.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_case_reminder_job_recipient_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'CaseReminderJobRecipient');
}

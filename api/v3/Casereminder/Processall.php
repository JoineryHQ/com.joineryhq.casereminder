<?php
use CRM_Casereminder_ExtensionUtil as E;

/**
 * Casereminder.Processall API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_casereminder_Processall_spec(&$spec) {
  $spec['timestamp'] = [
    'title' => 'Testing timestamp',
    'description' => 'Unix timestamp as an ad-hoc value for now(), used in testing',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Casereminder.Processall API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_casereminder_Processall($params) {
  // Set "now" timestamp if provided.
  CRM_Casereminder_Util_Time::singleton($params['timestamp'] ?? NULL);

  $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
  foreach ($nowReminderTypes as $nowReminderType) {
    if (!CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($nowReminderType)) {
      CRM_Casereminder_Util_Log::logReminderType($nowReminderType['id'], CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_BEGIN);
      // Get cases matching this reminder type.
      $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($nowReminderType);
      foreach ($reminderTypeCases as $reminderTypeCase) {
        if (
          !CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($nowReminderType, $reminderTypeCase)
//          && (FIXME: honor max_iterations.)
        ) {
          $recipientCids = CRM_Casereminder_Util_Casereminder::buildRecipientList($case, $reminderType);
          $sendingParams = CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams($reminderTypeCase, $nowReminderType);
          CRM_Casereminder_Util_Casereminder::sendCaseReminder($case['id'], $recipientCids, $sendingParams);
          CRM_Casereminder_Util_Log::logReminderCase($nowReminderType['id'], CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $reminderTypeCase['id']);
        }
      }
      CRM_Casereminder_Util_Log::logReminderType($nowReminderType['id'], CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE);
    }
  }

  return civicrm_api3_create_success($returnValues, $params, 'Casereminder', 'Processall');

}

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
  $spec['verbose'] = [
    'title' => 'Verbose',
    'description' => 'Provide verbose output',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_BOOLEAN,
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
  $verboseReturnValues = [
    'reminderTypesProcessed' => [
      'count' => 0,
      'ids' => [],
    ],
    'casesPerReminderType' => [
      /*
       * [reminderTypeId] => [
       *   'count' => [],
       *   'caseIds' => [],
       * ]
       */
    ],
  ];
  $briefReturnValues = [
    'reminderTypesProcessed' => 0,
    'totalCasesProcessed' => 0,
    'totalRemindersProcessed' => 0,
  ];
  $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
  foreach ($nowReminderTypes as $nowReminderType) {
    if (!CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($nowReminderType)) {
      $nowReminderTypeId = $nowReminderType['id'];
      $verboseReturnValues['casesPerReminderType'][$nowReminderTypeId]['caseIds'] = [];
      CRM_Casereminder_Util_Log::logReminderType($nowReminderTypeId, CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_BEGIN);
      // Get cases matching this reminder type.
      $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($nowReminderType);
      foreach ($reminderTypeCases as $reminderTypeCase) {
        if (
          !CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($nowReminderType, $reminderTypeCase)
          && !CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($nowReminderType, $reminderTypeCase)
          ) {
          $recipientCids = CRM_Casereminder_Util_Casereminder::buildRecipientList($reminderTypeCase, $nowReminderType);
          $sendingParams = CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams($reminderTypeCase, $nowReminderType);
          $sentCount = CRM_Casereminder_Util_Casereminder::sendCaseReminder($reminderTypeCase['id'], $nowReminderTypeId, $recipientCids, $sendingParams);
          $briefReturnValues['totalRemindersProcessed'] += $sentCount;
          $briefReturnValues['totalCasesProcessed']++;
          $verboseReturnValues['casesPerReminderType'][$nowReminderTypeId]['caseIds'][] = $reminderTypeCase['id'];
        }
      }
      CRM_Casereminder_Util_Log::logReminderType($nowReminderTypeId, CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE);
      $briefReturnValues['reminderTypesProcessed']++;
      $verboseReturnValues['reminderTypesProcessed']['ids'][] = $nowReminderTypeId;
    }
  }

  if ($params['verbose'] ?? NULL) {
    $verboseReturnValues['reminderTypesProcessed']['count'] = count($verboseReturnValues['reminderTypesProcessed']['ids']);
    foreach ($verboseReturnValues['casesPerReminderType'] as &$reminderTypeDetails) {
      $reminderTypeDetails['count'] = count($reminderTypeDetails['caseIds']);
    }
    $verboseReturnValues['summary'] = $briefReturnValues;
    $returnValues = $verboseReturnValues;
  }
  else {
    $returnValues = $briefReturnValues;
  }
  return civicrm_api3_create_success($returnValues, $params, 'Casereminder', 'Processall');

}

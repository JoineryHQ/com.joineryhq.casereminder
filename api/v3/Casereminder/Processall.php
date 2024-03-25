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
    'totalReminderTypesProcessed' => 0,
    'totalCasesProcessed' => 0,
    'totalRecipientsEnqueued' => 0,
    'totalRemindersSent' => 0,
    'totalRemindersSent-info' => NULL,
  ];
  $nowReminderTypes = CRM_Casereminder_Util_Casereminder::getNowReminderTypes();
  foreach ($nowReminderTypes as $nowReminderType) {
    if (!CRM_Casereminder_Util_Casereminder::reminderTypeCompletedToday($nowReminderType)) {
      $nowReminderTypeId = $nowReminderType['id'];
      $verboseReturnValues['casesPerReminderType'][$nowReminderTypeId]['caseIds'] = [];
      CRM_Casereminder_Util_Log::logReminderType($nowReminderTypeId, CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_BEGIN);

      // Create queue job for this remindertype:
      $caseReminderJob = CRM_Casereminder_Util_Queue::createReminderJob($nowReminderTypeId);

      // Get cases matching this reminder type.
      $reminderTypeCases = CRM_Casereminder_Util_Casereminder::getReminderTypeCases($nowReminderType);
      foreach ($reminderTypeCases as $reminderTypeCase) {
        if (
          !CRM_Casereminder_Util_Casereminder::reminderTypeCaseSentToday($nowReminderType, $reminderTypeCase)
          && !CRM_Casereminder_Util_Casereminder::reminderTypeCaseReachedMaxIterations($nowReminderType, $reminderTypeCase)
          ) {
          $recipientRolePerCid = CRM_Casereminder_Util_Casereminder::buildRecipientList($reminderTypeCase, $nowReminderType);
          $sendingParams = CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams($reminderTypeCase['id'], $nowReminderType);
          $enqueuedRecipientsCount = CRM_Casereminder_Util_Queue::enqueueCaseReminderRecipients($reminderTypeCase['id'], $recipientRolePerCid, $sendingParams, $caseReminderJob['id']);

          CRM_Casereminder_Util_Log::logReminderCase($nowReminderTypeId, CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $reminderTypeCase['id']);

          $briefReturnValues['totalRecipientsEnqueued'] += $enqueuedRecipientsCount;
          $briefReturnValues['totalCasesProcessed']++;
          $verboseReturnValues['casesPerReminderType'][$nowReminderTypeId]['caseIds'][] = $reminderTypeCase['id'];
        }
      }
      CRM_Casereminder_Util_Log::logReminderType($nowReminderTypeId, CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE);
      $briefReturnValues['totalReminderTypesProcessed']++;
      $verboseReturnValues['reminderTypesProcessed']['ids'][] = $nowReminderTypeId;
    }
  }

  // Now we've enqueued all appropriate recipients at the present moment; next, process the queue; this will include
  // any queued recipients that are still waiting from a previous invocation.
  $processQueueRet = CRM_Casereminder_Util_Queue::processQueue();
  CRM_Casereminder_Util_Casereminder::updateJobStatuses();

  if ($processQueueRet == -1) {
    $briefReturnValues['totalRemindersSent'] = 0;
    $briefReturnValues['totalRemindersSent-info'] = E::ts('Could not acquire lock. Is another job processing the queue?');
  }
  else {
    $briefReturnValues['totalRemindersSent'] = $processQueueRet;
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

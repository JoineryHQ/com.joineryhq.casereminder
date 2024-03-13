<?php

/**
 * Static utility methods for casereminder entity
 *
 */
class CRM_Casereminder_Util_Casereminder {

  /**
   * Get all reminders that are scheduled to fire at the present moment.
   *
   * @return Array
   *   caseReminderTypes, each one an array as returned by api caseReminderTypes.getSingle
   */
  public static function getNowReminderTypes() : array {
    $caseReminderTypeGet = _casereminder_civicrmapi('caseReminderType', 'get', [
      'dow' => CRM_Casereminder_Util_Time::singleton()->getDayOfWeek(),
      'is_active' => 1,
    ]);
    return $caseReminderTypeGet['values'];
  }

  /**
   * Get a list of all cases matching a given reminder type.
   *
   * @param array $reminderType Array of cases, each one as returned by api case.getSingle
   */
  public static function getReminderTypeCases(array $reminderType) : array {
    $apiParams = [
      'case_type_id' => $reminderType['case_type_id'],
      'is_deleted' => FALSE,
    ];
    if ($reminderType['case_status_id']) {
      $apiParams['status_id'] = ['IN' => $reminderType['case_status_id']];
    }

    $caseGet = _casereminder_civicrmapi('case', 'get', $apiParams);
    return $caseGet['values'];
  }

  /**
   * Send a reminder of a given type for a given case.
   * @param array $case Array of properties as returned by api Case.getSingle
   * @param array $reminderType Array of properties as returned by api CaseReminderType.getSingle
   *
   * @return Output of api email.send (provided by emailapi extension)
   */
  public static function prepCaseReminderSendingParams($case, $reminderType) {
    $fromEmailParts = self::splitFromEmail($reminderType['from_email_address']);

    $params = [
      // ID of the message template which will be used in the API.
      'template_id' => $reminderType['msg_template_id'],
      // optional adds the email to the case identified by this ID.
      'case_id' => $case['id'],
      // optional (default: 1) Record a copy of the email sent in an activity
      'create_activity' => TRUE,
      // optional (default: html,text) what to include in the details field of the created activity: HTML/Text/both versions, or just the name of the message template (it may be a disk space issue storing a full copy of everything on a busy site).
      'activity_details' => 'html',
      // optional name of the sender (if you provide this value you have also to provide from_email)
      'from_name' => $fromEmailParts['name'],
      // optional email of the sender (if you provide this value you have also to provide from_name)
      'from_email' => $fromEmailParts['email'],
      // email subjgect
      'subject' => $reminderType['subject'],
    ];
    return $params;
  }

  /**
   * Send a reminder of a given type for a given case.
   * @param int $caseId ID of the case.
   * @param array $recipientCids List of contactIds for recipients.
   * @param array $sendingParams As returned by e.g. self::prepCaseReminderSendingParams().
   *
   * @return void
   */
  public static function sendCaseReminder($caseId, $recipientCids, $sendingParams) {
    foreach ($recipientCids as $recipientCid) {
      $sendingParams['contact_id'] = $recipientCid;
      Civi::$statics['casreminder_token_case_id'] = $caseId;
      $emailSend = civicrm_api3('Email', 'send', $sendingParams);
      Civi::$statics['casreminder_token_case_id'] = NULL;
    }
  }

  public static function buildRecipientList($case, $reminderType) {

    // Case probably lacks the 'contacts' attribute, because it was created
    // (in self::getReminderTypeCases()) by case.get api without specifying an
    // id. That's frustrating but seems to be the reality of civicrm api3 at
    // the moment (unsure about api4).
    // Therefore, if it's not defined, we'll fetch it.
    if (!array_key_exists('contacts', $case)) {
      $case = _casereminder_civicrmapi('case', 'getSingle', ['id' => $case['id']]);
    }

    $recipientCids = [];
    $reminderTypeRelationshipTypeIds = $reminderType['recipient_relationship_type_id'];
    $caseClientId = reset($case['client_id']);

    // Add case client if called for.
    if (in_array(-1, $reminderTypeRelationshipTypeIds)) {
      $recipientCids[] = $caseClientId;
    }

    // Add each case contact if relationship_type_id is called for.
    if (is_array($case['contacts'])) {
      foreach ($case['contacts'] as $caseContact) {
        if (
          !empty($caseContact['relationship_type_id'])
          && in_array($caseContact['relationship_type_id'], $reminderTypeRelationshipTypeIds)
        ) {
          $recipientCids[] = $caseContact['contact_id'];
        }
      }
    }
    return array_unique($recipientCids);
  }

  /**
   * Has the given reminderType been processed today?
   *
   * @param array $reminderType as returned, e.g. by caseReminderType.getSingle api
   * @return boolean
   */
  public static function reminderTypeCompletedToday($reminderType) : bool {
    // We don't process reminderTypes twice on the same date.
    // So check whether this reminderType has been COMPLETED today.

    $todayRange = CRM_Casereminder_Util_Time::singleton()->getTodayRange();
    $apiParams = [
      'log_time' => ['BETWEEN' => $todayRange],
      'case_reminder_type_id' => $reminderType['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_REMINDER_TYPE_COMPLETE,
    ];
    $caseReminderLogTypeGet = _casereminder_civicrmapi('CaseReminderLogType', 'get', $apiParams);
    $caseReminderLogTypeCount = _casereminder_civicrmapi('CaseReminderLogType', 'getcount', $apiParams);
    if ($caseReminderLogTypeCount) {
      // "Completed" log entry found today; reminderType is not needed now.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Was the given case sent a reminder for the given reminderType, today?
   *
   * @param array $reminderType As returned by e.g. api caseReminderType.getSingle
   * @param array $case As returned by e.g. api case.getSingle
   * @return boolean
   */
  public static function reminderTypeCaseSentToday($reminderType, $case) : bool {
    // We don't send multiplereminders of the same ReminderType for the same case twice on the same date.
    // So check whether this reminderType has been used to send a reminder for this case today.
    $todayRange = CRM_Casereminder_Util_Time::singleton()->getTodayRange();
    $apiParams = [
      'log_time' => ['BETWEEN' => $todayRange],
      'case_reminder_type_id' => $reminderType['id'],
      'case_id' => $case['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_CASE_SEND,
    ];
    $caseReminderLogCaseCount = _casereminder_civicrmapi('CaseReminderLogCase', 'getcount', $apiParams);
    if ($caseReminderLogCaseCount) {
      // Case already has a sent reminder for this reminderType, so this reminder
      // is not needed now.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Split a given RFC 5322 address specification into display-name and address parts.
   * @param string $fromEmail e.g. '"Mickey Mouse"<mickey@mouse.example.com>'
   * @return array e.g. ['name' => 'Mickey Mouse', 'email' => 'mickey@mouse.example.com']
   */
  public static function splitFromEmail($fromEmail) : array {
    $ret = [];
    $ret['email'] = CRM_Utils_Mail::pluckEmailFromHeader($fromEmail);

    $nameParts = explode('"', $fromEmail);
    $ret['name'] = $nameParts[1];

    return $ret;
  }

}

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
      'options' => ['limit' => 0],
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
      'options' => ['limit' => 0],
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
   * @param array $taskContext queue task context
   * @param int $caseId ID of the case.
   * @param int $reminderTypeId ID of the reminderType.
   * @param array $recipientCids List of contactIds for recipients.
   * @param array $sendingParams As returned by e.g. self::prepCaseReminderSendingParams().
   *
   * @return int Total number of successful email.api calls (should be equal to count($recipientCids).
   */
  public static function sendQueuedCaseReminder($taskContext, $caseId, $reminderTypeId, $recipientCids, $sendingParams) {

    //    TODO:
    //      - this method should be changed to: sendQueuedCaseReminderEmail($taskContext, $caseId, $reminderTypeId, $recipientCid, $sendingParams).
    //        Note singular "$recipientCid". This method should only send a single email.
    //      - That means refactoring the processall api to enqueue individual emails instead of casereminders.
    //      - if there's an error in sending (e.g. bad smtp parameters), an exception will be thrown somewhere downstream,
    //        and that will stop the queue runner (and also mark this queue task as failed, to be retried later)
    //      - If there's no error in sending, task runner will have to count this as one email sent (against max of 'mailerBatchLimit'),
    //        even though emailapi may not send a mail (e.g. if recipient has no email address or address is marked on_hold).

    return FALSE;
    throw new CRM_Extension_Exception('foobar');
    $mailerSettings = CRM_Casereminder_Util_MailerSettings::singleton();

    $ret = 0;
    foreach ($recipientCids as $recipientCid) {
      $sendingParams['contact_id'] = $recipientCid;
      CRM_Casereminder_Util_Token::setTokenEnvCaseId($caseId);
      $emailSend = _casereminder_civicrmapi('Email', 'send', $sendingParams);
      if (($emailSend['is_error'] ?? NULL)) {
        var_dump($emailSend);
        return FALSE;
      }
      else {
        $ret++;
      }
      CRM_Casereminder_Util_Token::setTokenEnvCaseId(NULL);
      usleep($mailerSettings->get('mailThrottleTime'));
    }
    CRM_Casereminder_Util_Log::logReminderCase($reminderTypeId, CRM_Casereminder_Util_Log::ACTION_CASE_SEND, $caseId);
    return $ret;
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

  public static function reminderTypeCaseReachedMaxIterations($reminderType, $case) {
    $maxIterations = ($reminderType['max_iterations'] ?? '');

    // If maxiterations is empty or 0, we'll never reach it, so return false.
    if (empty($maxIterations)) {
      return FALSE;
    }

    // Maxiterations must be some number, so we need to count the logs and compare.
    $apiParams = [
      'case_reminder_type_id' => $reminderType['id'],
      'case_id' => $case['id'],
      'action' => CRM_Casereminder_Util_Log::ACTION_CASE_SEND,
    ];
    $caseReminderLogCaseCount = _casereminder_civicrmapi('CaseReminderLogCase', 'getcount', $apiParams);
    return ($caseReminderLogCaseCount >= $maxIterations);

  }

  public static function enqueueCaseReminder($caseId, $reminderTypeId, $recipientCids, $sendingParams) {

    //    TODO:
    //      - This method should be renamed to: enqueueCaseReminderEmails($caseId, $reminderTypeId, $recipientCids, $sendingParams),
    //        to enqueue one task per casereminder recipientCid (tasks to be run by self::sendQueuedCaseReminderEmail())

    // FIXME: refactor to new Queue class.
    $queueName = 'Casereminder_reminders';
    $queue = Civi::queue($queueName, [
      'type' => 'CasereminderSql',
      'runner' => 'task',
      'error' => 'abort',
      'status' => 'active',
      'retry_interval' => '10',
    ]);
    $queue->createItem(new CRM_Queue_Task(
      // callback:
      ['CRM_Casereminder_Util_Casereminder', 'sendQueuedCaseReminder'],
      // arguments:
      func_get_args(),
    ));
  }

  public static function processQueue() {
    // FIXME: refactor to new Queue class.

    // Acquire lock or return special "cannot get lock" value of -1.
    $casereminderLock = Civi::lockManager()->acquire('worker.casereminder.processqueue');
    if (!$casereminderLock->isAcquired()) {
      //      TODO:
      //        - instead of returning a magic value, throw a custom exception with recognizable code,
      //          and catch that exception in processall api.
      return -1;
    }

    $queueName = 'Casereminder_reminders';
    $queue = Civi::queue($queueName, [
      'type' => 'CasereminderSql',
      'runner' => 'task',
      'error' => 'abort',
      'status' => 'active',
      'retry_interval' => '10',
    ]);

    $runner = new CRM_Queue_Runner([
      'queue' => $queue,
    ]);

    $taskResult = $runner->formatTaskResult(TRUE);
    $itemSuccessCount = 0;

    $mailerSettings = CRM_Casereminder_Util_MailerSettings::singleton();
    $emailSendCount = 0;

    while ($taskResult['is_continue']) {
      $taskResult = $runner->runNext();
      if (!($taskResult['is_error'] ?? '')) {
        $itemSuccessCount++;
      }

      //      TODO:
      //        - Each item is a single email to a single recipient (though we cannot know)
      //          whether emailapi actually sent an email (e.g. if recipient has no email
      //          address or the mail address is on_hold). So our count may be off by
      //          some small amount.
      //        - If our count exceeds 'mailerBatchLimit', we should stop processing here.

      if ($itemSuccessCount >= $mailerSettings->get('mailerBatchLimit')) {
        break;
      }
    }

    return $itemSuccessCount;
  }

}

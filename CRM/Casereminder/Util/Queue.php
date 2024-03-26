<?php

/**
 * Utilities for queue management.
 *
 */
class CRM_Casereminder_Util_Queue {

  // Status strings for recipients. These may be user-visible, so desirable
  // characteristics are:
  // - Looks like an invariant name from a set list of values (thus all caps)
  // - Human-readable.
  const R_STATUS_QUEUED = 'QUEUED';
  const R_STATUS_DONE = 'DONE';
  const R_STATUS_QUEUED_ERROR = 'QUEUED WITH ERRORS';
  const R_STATUS_FAILED = 'FAILED';

  const QUEUE_NAME = 'Casereminder_reminders';
  const QUEUE_PARAMS = [
    'type' => 'CasereminderSql',
    'runner' => 'task',
    'error' => 'abort',
    'status' => 'active',
    // TODO: retry_interval is a number of seconds to wait before trying again on
    // failed tasks. What's the downside of setting this to 0 or NULL?
    'retry_interval' => '10',
    'lease_time' => '10',
  ];

  // 172800 seconds = 48 hours.
  const MAX_JOB_AGE_SECONDS = 172800;

  /**
   * Create a reminder job for a given reminderType.
   *
   * @param int $reminderTypeId
   * @return array A caseReminderJob, as returned by api caseReminderJob.create.
   */
  public static function createReminderJob(int $reminderTypeId) : array {
    $caseReminderJobCreate = _casereminder_civicrmapi('caseReminderJob', 'create', ['reminder_type_id' => $reminderTypeId]);
    $caseReminderJob = reset($caseReminderJobCreate['values']);
    return $caseReminderJob;
  }

  /**
   * Create a reminder job recipient with the given parameters.
   *
   * @param type $recipientCid
   * @param type $caseId
   * @param type $relationshipTypeId
   * @param type $jobId
   * @return array A caseReminderJobRecipient, as returned by api caseReminderJobRecipient.create (so, not necessarily the full entity).
   */
  public static function createRecipient(int $recipientCid, int $caseId, int $relationshipTypeId, int $jobId) : array {
    $apiParams = [
      'job_id' => $jobId,
      'case_id' => $caseId,
      'contact_id' => $recipientCid,
      'relationship_type_id' => ($relationshipTypeId == -1 ? NULL : $relationshipTypeId),
      'is_case_client' => (int) ($relationshipTypeId == -1),
    ];
    $caseReminderJobRecipientCreate = _casereminder_civicrmapi('caseReminderJobRecipient', 'create', $apiParams);
    $caseReminderJobRecipient = reset($caseReminderJobRecipientCreate['values']);
    return $caseReminderJobRecipient;
  }

  public static function createRecipientError(int $recipientId, string $errorMessage) : void {
    _casereminder_civicrmapi('caseReminderJobRecipientError', 'create', [
      'job_recipient_id' => $recipientId,
      'error_message' => $errorMessage,
    ]);
  }

  public static function jobIsOld(int $jobId) : bool {
    $job = _casereminder_civicrmapi('caseReminderJob', 'getsingle', ['id' => $jobId]);
    $jobCratedSeconds = strtotime($job['created']);
    $nowSeconds = time();
    return (($nowSeconds - $jobCratedSeconds) > self::MAX_JOB_AGE_SECONDS);
  }

  public static function extractEmailSendValues($emailSend) {
    $emailSendValues = $emailSend['values'] ?? [];
    $emailSendValue = reset($emailSendValues);
    $ret = [
      'sent' => (bool) ($emailSendValue['send'] ?? 0),
      'emailAddress' => str_replace('Successfully sent email to ', '', ($emailSendValue['status_msg'] ?? '')),
    ];
    return $ret;
  }

  public static function countEmailIfSent($emailSendValues) {
    if ($emailSendValues['sent']) {
      // We must let the runner know whether we've actually sent an email, because it's
      // capping itself at a maximum threshold of sent emails per run.
      //- If there's an error in sending (e.g. bad smtp parameters), an exception will be thrown somewhere downstream,
      //  and that will cause the runner to halt (and also to mark this queue task as failed, to be retried later)
      //- However, if there's no error in sending, but for good reason emailapi has not sent an email
      //  (e.g. if recipient has no email address or address is marked on_hold), we can't count
      //  that as a sent email, but he runner must also not treat it as an error (because
      //  it would just retry later). Instead, we want such queued tasks to be counted as done
      //  and removed from the queue.
      //
      //  Unfortunately, there's no way to return a useful value to the runner, other than
      //  TRUE or FALSE.
      //  Any exception is automatically counted as a failure that stops the runner and
      //  leaves the task in-queue.
      //  Therefore, just use a global static counter. Yuck. But fine.
      //
      \Civi::$statics[__CLASS__]['sentCount']++;
    }
  }

  public static function calculateRecipientStatus($isError, $jobId) {
    if (!$isError) {
      $ret = self::R_STATUS_DONE;
    }
    elseif (self::jobIsOld($jobId)) {
      $ret = self::R_STATUS_FAILED;
    }
    else {
      $ret = self::R_STATUS_QUEUED_ERROR;
    }
    return $ret;
  }

  /**
   * For a given job, create and enqueue recipients to be processed (emailed) later.
   *
   * @param int $caseId
   * @param array $recipientRolePerCid as returned by CRM_Casereminder_Util_Casereminder::buildRecipientList().
   * @param array $sendingParams CRM_Casereminder_Util_Casereminder::prepCaseReminderSendingParams().
   * @param int $jobId The case reminder job to which this recipient belongs.
   *
   * @return int Total recipients enqueued.
   */
  public static function enqueueCaseReminderRecipients(int $caseId, array $recipientRolePerCid, array $sendingParams, int $jobId) : int {
    // Initialize a counter to return.
    $ret = 0;

    // Create or get the queue by name.
    $queueName = self::QUEUE_NAME;
    $queue = Civi::queue($queueName, self::QUEUE_PARAMS);
    foreach ($recipientRolePerCid as $recipientCid => $relationshipTypeId) {
      // For each recipient contact, create and then enqueue a recipient (i.e. email).
      $recipient = self::createRecipient($recipientCid, $caseId, $relationshipTypeId, $jobId);
      $queue->createItem(new CRM_Queue_Task(
        // callback:
        ['CRM_Casereminder_Util_Queue', 'processQueuedRecipient'],
        // arguments:
        [
          $recipient['id'],
          $sendingParams,
        ],
      ));
      // Update recipient to indicate it has been queued.
      _casereminder_civicrmapi('caseReminderJobRecipient', 'create', [
        'id' => $recipient['id'],
        'status' => self::R_STATUS_QUEUED,
      ]);
      $ret++;
    }
    return $ret;
  }

  public static function processQueue() {
    // FIXME: refactor to new Queue class.
    // Acquire lock or return special "cannot get lock" value of -1.
    $casereminderLock = Civi::lockManager()->acquire('worker.casereminder.processqueue');
    if (!$casereminderLock->isAcquired()) {
      return -1;
    }

    $queueName = self::QUEUE_NAME;
    $queue = Civi::queue($queueName, self::QUEUE_PARAMS);

    $runner = new CRM_Queue_Runner([
      'queue' => $queue,
    ]);

    $taskResult = $runner->formatTaskResult(TRUE);

    // Global sstatic counter. see self::countEmailIfSent().
    \Civi::$statics[__CLASS__]['sentCount'] = 0;

    while ($taskResult['is_continue']) {
      $taskResult = $runner->runNext();
      $mailerBatchLimit = \Civi::settings()->get('mailerBatchLimit');
      if ($mailerBatchLimit && \Civi::$statics[__CLASS__]['sentCount'] >= $mailerBatchLimit) {
        // We've hit our limit. Stop here. We'll pick up remaining queued tasks next time.
        break;
      }
    }

    return \Civi::$statics[__CLASS__]['sentCount'];
  }

  /**
   * Send a reminder of a given type for a given case.
   * @param array $taskContext queue task context
   * @param array $recipientId The ID of the relevant caseReminderJobRecipient entity.
   * @param array $sendingParams As returned by e.g. self::prepCaseReminderSendingParams().
   *
   * @return int Total number of successful email.api calls (should be equal to count($recipientCids).
   */
  public static function processQueuedRecipient($taskContext, $recipientId, $sendingParams) {
    $recipient = _casereminder_civicrmapi('caseReminderJobRecipient', 'getSingle', ['id' => $recipientId]);
    $sendingParams['contact_id'] = $recipient['contact_id'];

    $updateRecipientParams = [
      'id' => $recipientId,
    ];

    $emailSend = [];
    $isError = FALSE;
    $errorMessage = '';

    CRM_Casereminder_Util_Token::setTokenEnvCaseId($recipient['case_id']);
    try {
      $emailSend = _casereminder_civicrmapi('Email', 'send', $sendingParams);
    }
    catch (Exception $e) {
      $isError = TRUE;
      self::createRecipientError($recipientId, $e->getMessage());
    }
    CRM_Casereminder_Util_Token::setTokenEnvCaseId(NULL);

    $emailSendValues = self::extractEmailSendValues($emailSend);
    self::countEmailIfSent($emailSendValues);

    $updateRecipientParams['sent_to'] = $emailSendValues['emailAddress'];
    $updateRecipientParams['status'] = self::calculateRecipientStatus($isError, $recipient['job_id']);

    _casereminder_civicrmapi('caseReminderJobRecipient', 'create', $updateRecipientParams);

    usleep(\Civi::settings()->get('mailThrottleTime'));

    // If this recipient had an error but remains in the queue for later processing,
    // we'll return false. Queue runner will try this one again later.
    // Otherwise, the recipient was either processed successfully or
    // is too old to bother with. Return true. Queue runner will remove it from the queue.
    return ($updateRecipientParams['status'] != self::R_STATUS_QUEUED_ERROR);
  }

}

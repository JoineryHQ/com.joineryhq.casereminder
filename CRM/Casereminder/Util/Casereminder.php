<?php

/**
 * Static utility methods for casereminder entity
 *
 */
class CRM_Casereminder_Util_Casereminder {
  
  /**
   * FIXME: Stub.
   * Get all reminders that are scheduled to fire at the present moment.
   */
  public static function getNowReminderTypes() : array {
    $now = CRM_Casereminder_Util_Time::singleton()->getNow();
    
    $caseReminderTypeGet = _casereminder_civicrmapi('caseReminderType', 'get', [
      'dow' => $now->format('l'),
      'is_active' => 1,
    ]);
//    FIXME: FIND A WAY TO SKIP REMINDERTYPES THAT HAVE ALREADY RUN TODAY.
    
    return $caseReminderTypeGet['values'];
  }
  
  /**
   * FIXME: Stub.
   * Get a list of all cases matching a given reminder type.
   * @param array $reminderType Array of properties as returned by api CaseReminderType.getSingle
   */
  public static function getReminderTypeCases(array $reminderType) : array {
    $jsonString = '
      {
        "id": "1",
        "case_type_id": "1",
        "subject": "test",
        "start_date": "2022-03-28",
        "status_id": "1",
        "is_deleted": "0",
        "created_date": "2022-03-28 17:24:11",
        "modified_date": "2024-02-08 14:00:04",
        "contact_id": {
            "1": "30"
        },
        "client_id": {
            "1": "30"
        }
      }
    ';    
    return array(
      json_decode($jsonString, TRUE),
    );
  }
  
  /**
   * FIXME: Stub.
   * Send a reminder of a given type for a given case.
   * @param array $case Array of properties as returned by api Case.getSingle
   * @param array $reminderType Array of properties as returned by api CaseReminderType.getSingle
   * 
   * @return Output of api email.send (provided by emailapi extension)
   */
  public static function sendCaseReminder($case, $reminderType) {
    $params = [
      // list of contacts IDs to create the PDF Letter (separated by ",")
      'contact_id' => 203,
      // ID of the message template which will be used in the API.
      'template_id' => 70,
      // optional alternative receiver address of the email.
      'alternative_receiver_address' => NULL,
      // optional adds the email to the case identified by this ID.
      'case_id' => $case['id'],
      // optional (default: 1) Record a copy of the email sent in an activity
      'create_activity' => TRUE,
      // optional (default: html,text) what to include in the details field of the created activity: HTML/Text/both versions, or just the name of the message template (it may be a disk space issue storing a full copy of everything on a busy site).
      'activity_details' => 'html',
      // optional option value of email addresses configured in Communications >> From Email Addresses
      'from_email_option' => 1,
      // email subjgect
      'subject' => "SUBJECT ". __METHOD__,
    ];
    $emailSend = civicrm_api3('Email', 'send', $params);
    return $emailSend;
  }
  
  public static function reminderTypeNeededNow($reminderType) : boolean {
    $time = CRM_Casereminder_Util_Time::singleton();
    die($time->getNow()->format('c'));
  }
}

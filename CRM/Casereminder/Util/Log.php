<?php

/**
 * Utilities for logging.
 *
 */
class CRM_Casereminder_Util_Log {
  
  const ACTION_REMINDER_TYPE_BEGIN = 'BEGIN';
  const ACTION_REMINDER_TYPE_COMPLETE = 'COMPLETE';
  const ACTION_CASE_SEND = 'SEND';
  
  public static function logReminderType ($caseReminderTypeId, $action) {
    $validActions = [
      self::ACTION_REMINDER_TYPE_BEGIN,
      self::ACTION_REMINDER_TYPE_COMPLETE,
    ];
    if (!in_array($action, $validActions)) {
      throw new CRM_Extension_Exception("Invalid action '$action'", 'invalid_action', ['valid actions' => $validActions]);
    }
    _casereminder_civicrmapi('caseReminderLogType', 'create', [
      'case_reminder_type_id' => $caseReminderTypeId,
      'action' => $action,
      'log_time' => CRM_Casereminder_Util_Time::singleton()->getMysqlDatetime(),
    ]);
  }
  
  /**
   * Log reminderType processing on a specific case.
   * @param type $caseReminderTypeId
   * @param type $action
   * @throws CRM_Extension_Exception
   */
  public static function logReminderCase ($caseReminderTypeId, $action, $caseId) {
    $validActions = [
      self::ACTION_CASE_SEND,
    ];
    if (!in_array($action, $validActions)) {
      throw new CRM_Extension_Exception("Invalid action '$action'", 'invalid_action', ['valid actions' => $validActions]);
    }
    _casereminder_civicrmapi('caseReminderLogCase', 'create', [
      'case_reminder_type_id' => $caseReminderTypeId,
      'case_id' => $caseId,
      'action' => $action,
      'log_time' => CRM_Casereminder_Util_Time::singleton()->getMysqlDatetime(),
    ]);
  }
}

<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $job_id
 * @property string $case_id
 * @property string $contact_id
 * @property bool|string $is_case_client
 * @property string $relationship_type_id
 * @property string $sent_to
 * @property string $status
 * @property string $status_time
 */
class CRM_Casereminder_DAO_CaseReminderJobRecipient extends CRM_Casereminder_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_case_reminder_job_recipient';

}

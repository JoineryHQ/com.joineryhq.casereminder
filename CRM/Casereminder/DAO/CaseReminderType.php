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
 * @property string $case_type_id
 * @property string $case_status_id
 * @property string $msg_template_id
 * @property string $recipient_relationship_type_id
 * @property string $from_email_address
 * @property string $subject
 * @property string $dow
 * @property string $max_iterations
 * @property bool|string $is_active
 */
class CRM_Casereminder_DAO_CaseReminderType extends CRM_Casereminder_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_case_reminder_type';

}

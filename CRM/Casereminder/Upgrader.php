<?php

use CRM_Casereminder_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Casereminder_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   * Note that if a file is present sql\auto_install that will run regardless of this hook.
   */
  // public function install(): void {
  //   $this->executeSqlFile('sql/my_install.sql');
  // }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  // public function postInstall(): void {
  //  $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
  //    'return' => array("id"),
  //    'name' => "customFieldCreatedViaManagedHook",
  //  ));
  //  civicrm_api3('Setting', 'create', array(
  //    'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
  //  ));
  // }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
   * Note that if a file is present sql\auto_uninstall that will run regardless of this hook.
   */
  // public function uninstall(): void {
  //   $this->executeSqlFile('sql/my_uninstall.sql');
  // }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable(): void {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable(): void {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   * Add log tables
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  public function upgrade_4200(): bool {
    $this->ctx->log->info('Applying update 4200: Add log tables');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_case_reminder_log_case`');
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_case_reminder_log_case` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CaseReminderLogCase ID',
        `log_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When log entry created.',
        `case_reminder_type_id` int unsigned NOT NULL COMMENT 'FK to Reminder Type',
        `case_id` int unsigned NOT NULL COMMENT 'FK to Case',
        `action` varchar(255) NOT NULL COMMENT 'Standardized description of action logged',
        PRIMARY KEY (`id`),
        INDEX `action`(action),
        CONSTRAINT FK_civicrm_case_reminder_log_case_case_reminder_type_id FOREIGN KEY (`case_reminder_type_id`) REFERENCES `civicrm_case_reminder_type`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_case_reminder_log_case_case_id FOREIGN KEY (`case_id`) REFERENCES `civicrm_case`(`id`) ON DELETE CASCADE
      )
      ENGINE=InnoDB;
    ");

    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_case_reminder_log_type`');
    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_case_reminder_log_type` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CaseReminderLogType ID',
        `log_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When log entry created.',
        `case_reminder_type_id` int unsigned NOT NULL COMMENT 'FK to Reminder Type',
        `action` varchar(255) NOT NULL COMMENT 'Standardized description of action logged',
        PRIMARY KEY (`id`),
        INDEX `action`(action),
        CONSTRAINT FK_civicrm_case_reminder_log_type_case_reminder_type_id FOREIGN KEY (`case_reminder_type_id`) REFERENCES `civicrm_case_reminder_type`(`id`) ON DELETE CASCADE
      )
      ENGINE=InnoDB;
    ");
    return TRUE;
  }

  /**
   * Add queue tables
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  public function upgrade_4205(): bool {
    $this->ctx->log->info('Applying update 4205: Add queue tables');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_case_reminder_job_recipient_error`');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_case_reminder_job_recipient`');
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_case_reminder_job`');

    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_case_reminder_job` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CaseReminderLogJob ID',
        `reminder_type_id` int unsigned COMMENT 'FK to reminderType',
        `start` datetime NULL COMMENT 'When queue processing began for this job.',
        `end` datetime NULL COMMENT 'When queue processing was completed for this job.',
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When log entry created.',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_case_reminder_job_reminder_type_id FOREIGN KEY (`reminder_type_id`) REFERENCES `civicrm_case_reminder_type`(`id`) ON DELETE CASCADE
      )
      ENGINE=InnoDB;
    ");

    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_case_reminder_job_recipient` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CaseReminderLogRecipient ID',
        `job_id` int unsigned NOT NULL COMMENT 'FK to casereminder job',
        `case_id` int unsigned NOT NULL COMMENT 'FK to Case',
        `contact_id` int unsigned NOT NULL COMMENT 'FK to Contact',
        `is_case_client` tinyint NOT NULL COMMENT 'Is this recipient the case client?',
        `relationship_type_id` int unsigned COMMENT 'Case Role relationship type',
        `sent_to` varchar(254) NULL COMMENT 'Email address to which reminder was sent (if any)',
        `status` varchar(255) COMMENT 'Standardized description of recipient status',
        `status_time` datetime NULL COMMENT 'When was status updated?',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_case_reminder_job_recipient_job_id FOREIGN KEY (`job_id`) REFERENCES `civicrm_case_reminder_job`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_case_reminder_job_recipient_case_id FOREIGN KEY (`case_id`) REFERENCES `civicrm_case`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_case_reminder_job_recipient_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
        CONSTRAINT FK_civicrm_case_reminder_job_recipient_relationship_type_id FOREIGN KEY (`relationship_type_id`) REFERENCES `civicrm_relationship_type`(`id`) ON DELETE SET NULL
      )
      ENGINE=InnoDB;
    ");

    CRM_Core_DAO::executeQuery("
      CREATE TABLE `civicrm_case_reminder_job_recipient_error` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CaseReminderJobRecipientError ID',
        `job_recipient_id` int unsigned NOT NULL COMMENT 'FK to casereminder job recipient',
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When log entry created.',
        `error_message` varchar(255) NOT NULL COMMENT 'Error message',
        PRIMARY KEY (`id`),
        CONSTRAINT FK_civicrm_case_reminder_job_recipient_error_job_recipient_id FOREIGN KEY (`job_recipient_id`) REFERENCES `civicrm_case_reminder_job_recipient`(`id`) ON DELETE CASCADE
      )
      ENGINE=InnoDB;
    ");

    return TRUE;
  }

  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4201(): bool {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }

  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4202(): bool {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4203(): bool {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = apple(banana()+durian)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

}

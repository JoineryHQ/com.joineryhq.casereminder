<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  'name' => 'CaseReminderJobRecipientError',
  'table' => 'civicrm_case_reminder_job_recipient_error',
  'class' => 'CRM_Casereminder_DAO_CaseReminderJobRecipientError',
  'getInfo' => fn() => [
    'title' => E::ts('Case Reminder Job Recipient Error'),
    'title_plural' => E::ts('Case Reminder Job Recipient Errors'),
    'description' => E::ts('Errors in queue processing for job recipients'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CaseReminderJobRecipientError ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'job_recipient_id' => [
      'title' => E::ts('Job Recipient ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to casereminder job recipient'),
      'usage' => ['export'],
      'entity_reference' => [
        'entity' => 'CaseReminderJobRecipient',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'created' => [
      'title' => E::ts('Created'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => E::ts('When log entry created.'),
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
    'error_message' => [
      'title' => E::ts('Error Message'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Error message'),
      'usage' => ['export'],
    ],
  ],
];

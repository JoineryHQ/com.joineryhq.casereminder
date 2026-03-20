<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  'name' => 'CaseReminderJob',
  'table' => 'civicrm_case_reminder_job',
  'class' => 'CRM_Casereminder_DAO_CaseReminderJob',
  'getInfo' => fn() => [
    'title' => E::ts('Case Reminder Job'),
    'title_plural' => E::ts('Case Reminder Jobs'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
  ],
  'getPaths' => fn() => [
    'view' => '/civicrm/admin/casereminder/jobrecipients#/?job=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CaseReminderLogJob ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'reminder_type_id' => [
      'title' => E::ts('Reminder Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to reminderType'),
      'entity_reference' => [
        'entity' => 'CaseReminderType',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'start' => [
      'title' => E::ts('Start'),
      'sql_type' => 'datetime',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('When queue processing began for this job.'),
      'usage' => ['export'],
    ],
    'end' => [
      'title' => E::ts('End'),
      'sql_type' => 'datetime',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('When queue processing was completed for this job.'),
      'usage' => ['export'],
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
  ],
];

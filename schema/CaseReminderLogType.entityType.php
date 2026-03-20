<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  'name' => 'CaseReminderLogType',
  'table' => 'civicrm_case_reminder_log_type',
  'class' => 'CRM_Casereminder_DAO_CaseReminderLogType',
  'getInfo' => fn() => [
    'title' => E::ts('Case Reminder Log Type'),
    'title_plural' => E::ts('Case Reminder Log Types'),
    'description' => E::ts('Logs for Case Reminder Types'),
    'log' => TRUE,
  ],
  'getIndices' => fn() => [
    'action' => [
      'fields' => [
        'action' => TRUE,
      ],
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CaseReminderLogType ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'log_time' => [
      'title' => E::ts('Log Time'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('When log entry created.'),
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
    'case_reminder_type_id' => [
      'title' => E::ts('Case Reminder Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Reminder Type'),
      'usage' => ['export'],
      'entity_reference' => [
        'entity' => 'CaseReminderType',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'action' => [
      'title' => E::ts('Action'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Standardized description of action logged'),
      'usage' => ['export'],
    ],
  ],
];

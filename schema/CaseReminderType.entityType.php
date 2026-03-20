<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  'name' => 'CaseReminderType',
  'table' => 'civicrm_case_reminder_type',
  'class' => 'CRM_Casereminder_DAO_CaseReminderType',
  'getInfo' => fn() => [
    'title' => E::ts('Case Reminder Type'),
    'title_plural' => E::ts('Case Reminder Types'),
    'description' => E::ts('Case Reminder configurations per reminder type'),
    'log' => TRUE,
  ],
  'getPaths' => fn() => [
    'view' => '/civicrm/admin/casereminder/jobs#/?reminderType=[id]',
    'add' => '/civicrm/admin/casereminder/type/?action=add&reset=1',
    'update' => '/civicrm/admin/casereminder/type/?action=update&reset=1&id=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CaseReminderType ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'case_type_id' => [
      'title' => E::ts('Case Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Case Type'),
      'pseudoconstant' => [
        'option_group_name' => 'case_type',
      ],
      'entity_reference' => [
        'entity' => 'CaseType',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'case_status_id' => [
      'title' => E::ts('Case Status ID'),
      'sql_type' => 'varchar(256)',
      'input_type' => 'Select',
      'description' => E::ts('Case status ID(s), multi-value delimited. Implicit FK to civicrm_option_value where option_group = case_status.'),
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND'),
      'pseudoconstant' => [
        'option_group_name' => 'case_status',
      ],
    ],
    'msg_template_id' => [
      'title' => E::ts('Msg Template ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to civicrm_msg_template'),
      'entity_reference' => [
        'entity' => 'MessageTemplate',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'recipient_relationship_type_id' => [
      'title' => E::ts('Recipient Relationship Type ID'),
      'sql_type' => 'varchar(256)',
      'input_type' => 'Select',
      'description' => E::ts('Relationship type ID(s) for recipients, multi-value delimited. Implicit FK to civicrm_relationship_type. -1 is Case Contact.'),
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND'),
      'pseudoconstant' => [
        'callback' => 'CRM_Casereminder_BAO_CaseReminderType::getRecipientOptions',
      ],
    ],
    'from_email_address' => [
      'title' => E::ts('From Email Address'),
      'sql_type' => 'varchar(256)',
      'input_type' => 'Text',
      'description' => E::ts('Email address selected from domain list of From addresses.'),
    ],
    'subject' => [
      'title' => E::ts('Subject'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Subject of reminder email'),
    ],
    'dow' => [
      'title' => E::ts('Dow'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => E::ts('Day of week to send reminders'),
      'pseudoconstant' => [
        'callback' => 'CRM_Casereminder_BAO_CaseReminderType::getDowOptions',
      ],
    ],
    'max_iterations' => [
      'title' => E::ts('Max Iterations'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('Maximum number of times to send a reminder of this type on any given case.'),
    ],
    'is_active' => [
      'title' => E::ts('Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => E::ts('Is this reminder type active?'),
    ],
  ],
];

<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  'name' => 'CaseReminderJobRecipient',
  'table' => 'civicrm_case_reminder_job_recipient',
  'class' => 'CRM_Casereminder_DAO_CaseReminderJobRecipient',
  'getInfo' => fn() => [
    'title' => E::ts('Case Reminder Job Recipient'),
    'title_plural' => E::ts('Case Reminder Job Recipients'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
  ],
  'getPaths' => fn() => [
    'view' => '/civicrm/admin/casereminder/jobrecipientErrors#/?recipientId=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CaseReminderLogRecipient ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'job_id' => [
      'title' => E::ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to casereminder job'),
      'usage' => ['export'],
      'entity_reference' => [
        'entity' => 'CaseReminderJob',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'case_id' => [
      'title' => E::ts('Case ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Case'),
      'usage' => ['export'],
      'entity_reference' => [
        'entity' => 'Case',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('FK to Contact'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_case_client' => [
      'title' => E::ts('Is Case Client'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Is this recipient the case client?'),
    ],
    'relationship_type_id' => [
      'title' => E::ts('Case Role Relationship Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => E::ts('Case Role relationship type'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => E::ts('Relationship Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_relationship_type',
        'key_column' => 'id',
        'name_column' => 'name_a_b',
        'label_column' => 'label_a_b',
      ],
      'entity_reference' => [
        'entity' => 'RelationshipType',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'sent_to' => [
      'title' => E::ts('Sent To'),
      'sql_type' => 'varchar(254)',
      'input_type' => 'Text',
      'description' => E::ts('Email address to which reminder was sent (if any)'),
    ],
    'status' => [
      'title' => E::ts('Status'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('Standardized description of recipient status'),
      'usage' => ['export'],
    ],
    'status_time' => [
      'title' => E::ts('Status Time'),
      'sql_type' => 'datetime',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('When was status updated?'),
      'usage' => ['export'],
    ],
  ],
];

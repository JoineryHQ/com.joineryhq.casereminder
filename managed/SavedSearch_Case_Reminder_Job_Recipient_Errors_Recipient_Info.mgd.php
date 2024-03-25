<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipient_Errors_Recipient_Info',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipient_Errors_Recipient_Info',
        'label' => E::ts('Case Reminder Job Recipient Report: Recipient Info'),
        'api_entity' => 'CaseReminderJobRecipient',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'job_id',
            'contact_id',
            'contact_id.display_name',
            'IFNULL(status, "Not yet processed") AS IFNULL_status',
            'IFNULL(status_time, "Not yet processed") AS IFNULL_status_time',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipient_Errors_Recipient_Info_SearchDisplay_Case_Reminder_Job_Recipient_Errors_Recipient_Info_List_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipient_Errors_Recipient_Info_List_1',
        'label' => E::ts('Case Reminder Job Recipient Errors: Recipient Info List 1'),
        'saved_search_id.name' => 'Case_Reminder_Job_Recipient_Errors_Recipient_Info',
        'type' => 'list',
        'settings' => [
          'style' => 'ul',
          'limit' => 1,
          'sort' => [],
          'pager' => FALSE,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'break' => TRUE,
              'label' => E::ts('Recipient ID:'),
            ],
            [
              'type' => 'html',
              'key' => 'job_id',
              'dataType' => 'Integer',
              'break' => TRUE,
              'label' => E::ts('For Job ID:'),
              'rewrite' => 'ID = [job_id] <a href="/civicrm/admin/casereminder/jobrecipients#/?job=[job_id]">Back to Job Report</a>',
            ],
            [
              'type' => 'field',
              'key' => 'contact_id',
              'dataType' => 'Integer',
              'break' => TRUE,
              'label' => E::ts('Contact ID:'),
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'break' => TRUE,
              'label' => E::ts('Contact Name:'),
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '',
              ],
              'title' => E::ts('View Contact ID'),
            ],
            [
              'type' => 'field',
              'key' => 'IFNULL_status',
              'dataType' => 'String',
              'break' => TRUE,
              'label' => E::ts('Recipient Status:'),
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'IFNULL_status_time',
              'dataType' => 'String',
              'break' => TRUE,
              'label' => E::ts('Status Updated:'),
              'forceLabel' => TRUE,
            ],
          ],
          'placeholder' => 5,
          'symbol' => 'none',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
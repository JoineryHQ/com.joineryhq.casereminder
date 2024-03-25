<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipient_Errors',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipient_Errors',
        'label' => E::ts('Case Reminder Job Recipient Report'),
        'api_entity' => 'CaseReminderJobRecipientError',
        'api_params' => [
          'version' => 4,
          'select' => [
            'created',
            'error_message',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'CaseReminderJobRecipient AS CaseReminderJobRecipientError_CaseReminderJobRecipient_job_recipient_id_01',
              'INNER',
              [
                'job_recipient_id',
                '=',
                'CaseReminderJobRecipientError_CaseReminderJobRecipient_job_recipient_id_01.id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipient_Errors_SearchDisplay_Case_Reminder_Job_Recipient_Errors_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipient_Errors_Table_1',
        'label' => E::ts('Case Reminder Job Recipient Errors Table 1'),
        'saved_search_id.name' => 'Case_Reminder_Job_Recipient_Errors',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'created',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'created',
              'dataType' => 'Timestamp',
              'label' => E::ts('Error Time'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'error_message',
              'dataType' => 'String',
              'label' => E::ts('Error Message'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Jobs',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Jobs',
        'label' => E::ts('Case Reminder Type Report'),
        'api_entity' => 'CaseReminderJob',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'created',
            'start',
            'end',
            'COUNT(CaseReminderJob_CaseReminderJobRecipient_job_id_01_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01.id) AS COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01_id',
            'COUNT(CaseReminderJob_CaseReminderJobRecipient_job_id_01.id) AS COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_id',
            'reminder_type_id',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'CaseReminderJobRecipient AS CaseReminderJob_CaseReminderJobRecipient_job_id_01',
              'LEFT',
              [
                'id',
                '=',
                'CaseReminderJob_CaseReminderJobRecipient_job_id_01.job_id',
              ],
            ],
            [
              'CaseReminderJobRecipientError AS CaseReminderJob_CaseReminderJobRecipient_job_id_01_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01',
              'LEFT',
              [
                'CaseReminderJob_CaseReminderJobRecipient_job_id_01.id',
                '=',
                'CaseReminderJob_CaseReminderJobRecipient_job_id_01_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01.job_recipient_id',
              ],
            ],
            [
              'CaseReminderType AS CaseReminderJob_CaseReminderType_reminder_type_id_01',
              'INNER',
              [
                'reminder_type_id',
                '=',
                'CaseReminderJob_CaseReminderType_reminder_type_id_01.id',
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
    'name' => 'SavedSearch_Case_Reminder_Jobs_SearchDisplay_Case_Reminder_Jobs_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Jobs_Table_1',
        'label' => E::ts('Case Reminder: Jobs Table 1'),
        'saved_search_id.name' => 'Case_Reminder_Jobs',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'id',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'created',
              'dataType' => 'Timestamp',
              'label' => E::ts('Created'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'start',
              'dataType' => 'Timestamp',
              'label' => E::ts('Start'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'end',
              'dataType' => 'Timestamp',
              'label' => E::ts('End'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Recipient count'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Error count'),
              'sortable' => TRUE,
            ],
            [
              'links' => [
                [
                  'path' => '',
                  'icon' => '',
                  'text' => E::ts('View Recipients'),
                  'style' => 'default',
                  'condition' => [
                    'COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_id',
                    '>=',
                    '1',
                  ],
                  'task' => '',
                  'entity' => 'CaseReminderJob',
                  'action' => 'view',
                  'join' => '',
                  'target' => '',
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
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
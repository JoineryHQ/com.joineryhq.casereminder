<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipients_Job_Info',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipients_Job_Info',
        'label' => E::ts('Case Reminder Job Report: Job Info'),
        'api_entity' => 'CaseReminderJob',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'start',
            'end',
            'COUNT(CaseReminderJob_CaseReminderJobRecipient_job_id_01.id) AS COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_id',
            'COUNT(DISTINCT CaseReminderJob_CaseReminderJobRecipient_job_id_01.case_id) AS COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_case_id',
            'reminder_type_id',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
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
    'name' => 'SavedSearch_Case_Reminder_Job_Recipients_Job_Info_SearchDisplay_Case_Reminder_Job_Recipients_Job_Info_List_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipients_Job_Info_List_1',
        'label' => E::ts('Case Reminder Job Recipients: Job Info List 1'),
        'saved_search_id.name' => 'Case_Reminder_Job_Recipients_Job_Info',
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
              'label' => E::ts('Job ID:'),
              'break' => TRUE,
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'reminder_type_id',
              'dataType' => 'Integer',
              'label' => E::ts('For Reminder Type:'),
              'title' => E::ts('Back to Reminder Type report'),
              'rewrite' => 'ID = [reminder_type_id] <a href="/civicrm/admin/casereminder/jobs#/?reminderType=[reminder_type_id]">Back to Reminder Type Report</a>',
              'break' => TRUE,
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'start',
              'dataType' => 'Timestamp',
              'label' => E::ts('Start:'),
              'break' => TRUE,
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'end',
              'dataType' => 'Timestamp',
              'label' => E::ts('End:'),
              'break' => TRUE,
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_case_id',
              'dataType' => 'Integer',
              'break' => TRUE,
              'label' => E::ts('Cases:'),
              'forceLabel' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_CaseReminderJob_CaseReminderJobRecipient_job_id_01_id',
              'dataType' => 'Integer',
              'break' => TRUE,
              'label' => E::ts('Recipients:'),
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
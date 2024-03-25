<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Job_Recipients',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipients',
        'label' => E::ts('Case Reminder Job Report'),
        'api_entity' => 'CaseReminderJobRecipient',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'job_id',
            'case_id',
            'GROUP_CONCAT(DISTINCT CaseReminderJobRecipient_Case_case_id_01.subject) AS GROUP_CONCAT_CaseReminderJobRecipient_Case_case_id_01_subject',
            'GROUP_CONCAT(DISTINCT CaseReminderJobRecipient_RelationshipType_relationship_type_id_01.label_a_b) AS GROUP_CONCAT_CaseReminderJobRecipient_RelationshipType_relationship_type_id_01_label_a_b',
            'contact_id.display_name',
            'status',
            'sent_to',
            'COUNT(CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01.id) AS COUNT_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01_id',
            'IF(is_case_client, "1", 0) AS IF_is_case_client',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'CaseReminderJob AS CaseReminderJobRecipient_CaseReminderJob_job_id_01',
              'INNER',
              [
                'job_id',
                '=',
                'CaseReminderJobRecipient_CaseReminderJob_job_id_01.id',
              ],
            ],
            [
              'Contact AS CaseReminderJobRecipient_Contact_contact_id_01',
              'INNER',
              [
                'contact_id',
                '=',
                'CaseReminderJobRecipient_Contact_contact_id_01.id',
              ],
            ],
            [
              'Case AS CaseReminderJobRecipient_Case_case_id_01',
              'INNER',
              [
                'case_id',
                '=',
                'CaseReminderJobRecipient_Case_case_id_01.id',
              ],
            ],
            [
              'RelationshipType AS CaseReminderJobRecipient_RelationshipType_relationship_type_id_01',
              'LEFT',
              [
                'relationship_type_id',
                '=',
                'CaseReminderJobRecipient_RelationshipType_relationship_type_id_01.id',
              ],
            ],
            [
              'CaseReminderJobRecipientError AS CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01',
              'LEFT',
              [
                'id',
                '=',
                'CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01.job_recipient_id',
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
    'name' => 'SavedSearch_Case_Reminder_Job_Recipients_SearchDisplay_Case_Reminder_Job_Recipients_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Job_Recipients_Table_1',
        'label' => E::ts('Case Reminder Job Recipients Table 1'),
        'saved_search_id.name' => 'Case_Reminder_Job_Recipients',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_CaseReminderJobRecipient_Case_case_id_01_subject',
              'dataType' => 'String',
              'label' => E::ts('Case Subject'),
              'sortable' => TRUE,
              'title' => E::ts('View Case'),
              'link' => [
                'path' => '',
                'entity' => 'Case',
                'action' => 'view',
                'join' => 'CaseReminderJobRecipient_Case_case_id_01',
                'target' => '',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'case_id',
              'dataType' => 'Integer',
              'label' => E::ts('Case ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => E::ts('Recipient Name'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'contact_id',
                'target' => '',
              ],
              'title' => E::ts('View Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'status',
              'dataType' => 'String',
              'label' => E::ts('Sending Status'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'sent_to',
              'dataType' => 'String',
              'label' => E::ts('Sent to Email'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'IF_is_case_client',
              'dataType' => 'String',
              'label' => E::ts('Case Role'),
              'sortable' => TRUE,
              'rewrite' => '{if [IF_is_case_client]} 
  {ts}Case Client{/ts}
{else}
  [GROUP_CONCAT_CaseReminderJobRecipient_RelationshipType_relationship_type_id_01_label_a_b]
{/if}',
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Error count'),
              'sortable' => TRUE,
            ],
            [
              'links' => [
                [
                  'entity' => 'CaseReminderJobRecipient',
                  'action' => 'view',
                  'join' => '',
                  'target' => '',
                  'icon' => '',
                  'text' => E::ts('View errors'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'condition' => [
                    'COUNT_CaseReminderJobRecipient_CaseReminderJobRecipientError_job_recipient_id_01_id',
                    '>=',
                    '1',
                  ],
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
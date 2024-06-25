<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Types',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Types',
        'label' => E::ts('Case Reminder Types'),
        'api_entity' => 'CaseReminderType',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'CaseReminderType_CaseType_case_type_id_01.title',
            'case_status_id:label',
            'CaseReminderType_MessageTemplate_msg_template_id_01.msg_title',
            'subject',
            'dow:label',
            'is_active',
            'recipient_relationship_type_id:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'CaseType AS CaseReminderType_CaseType_case_type_id_01',
              'INNER',
              [
                'case_type_id',
                '=',
                'CaseReminderType_CaseType_case_type_id_01.id',
              ],
            ],
            [
              'MessageTemplate AS CaseReminderType_MessageTemplate_msg_template_id_01',
              'LEFT',
              [
                'msg_template_id',
                '=',
                'CaseReminderType_MessageTemplate_msg_template_id_01.id',
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
    'name' => 'SavedSearch_Case_Reminder_Types_SearchDisplay_Case_Reminder_Types_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Types_Table_1',
        'label' => E::ts('Case Reminder Types Table 1'),
        'saved_search_id.name' => 'Case_Reminder_Types',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'id',
              'ASC',
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
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'CaseReminderType_CaseType_case_type_id_01.title',
              'dataType' => 'String',
              'label' => E::ts('Case Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'case_status_id:label',
              'dataType' => 'String',
              'label' => E::ts('Case Statuses'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'recipient_relationship_type_id:label',
              'dataType' => 'String',
              'label' => E::ts('Recipients'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'CaseReminderType_MessageTemplate_msg_template_id_01.msg_title',
              'dataType' => 'String',
              'label' => E::ts('Message Template'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'dataType' => 'String',
              'label' => E::ts('Subject'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'dow:label',
              'dataType' => 'String',
              'label' => E::ts('Send On'),
              'sortable' => TRUE,
            ],
            [
              'links' => [
                [
                  'path' => '',
                  'icon' => '',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => 'CaseReminderType',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'delete',
                  'entity' => 'CaseReminderType',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => '',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'action' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'CaseReminderType',
                  'action' => 'view',
                  'join' => '',
                  'target' => '',
                  'icon' => '',
                  'text' => E::ts('Report'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
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
          'cssRules' => [
            [
              'disabled',
              'is_active',
              '=',
              FALSE,
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'CaseReminderType',
              'text' => E::ts('Add Case Reminder Type'),
              'icon' => 'fa-plus',
              'target' => 'crm-popup',
              'action' => 'add',
              'style' => 'primary',
              'join' => '',
              'path' => '',
              'task' => '',
              'condition' => [],
            ],
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

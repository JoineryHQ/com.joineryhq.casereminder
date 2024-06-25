<?php
use CRM_Casereminder_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Case_Reminder_Type',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Type',
        'label' => E::ts('Case Reminder Type Report: Type Info'),
        'api_entity' => 'CaseReminderType',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'CaseReminderType_CaseType_case_type_id_01.title',
            'subject',
            'CaseReminderType_MessageTemplate_msg_template_id_01.msg_title',
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
              'INNER',
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
    'name' => 'SavedSearch_Case_Reminder_Type_SearchDisplay_Case_Reminder_Type_Search_by_Admin_Nistrator_List_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Case_Reminder_Type_Search_by_Admin_Nistrator_List_1',
        'label' => E::ts('Case Reminder Type List'),
        'saved_search_id.name' => 'Case_Reminder_Type',
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
              'label' => E::ts('Reminder Type ID:'),
              'break' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'CaseReminderType_CaseType_case_type_id_01.title',
              'dataType' => 'String',
              'label' => E::ts('Case Type:'),
              'break' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'dataType' => 'String',
              'break' => TRUE,
              'label' => E::ts('Subject:'),
            ],
            [
              'type' => 'field',
              'key' => 'CaseReminderType_MessageTemplate_msg_template_id_01.msg_title',
              'dataType' => 'String',
              'label' => E::ts('Message Template:'),
              'break' => TRUE,
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

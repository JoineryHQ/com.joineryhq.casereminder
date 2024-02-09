<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'CaseReminderType',
    'class' => 'CRM_Casereminder_DAO_CaseReminderType',
    'table' => 'civicrm_case_reminder_type',
  ],
];

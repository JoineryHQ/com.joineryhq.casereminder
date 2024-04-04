<?php

use CRM_Casereminder_ExtensionUtil as E;

class CRM_Casereminder_BAO_CaseReminderType extends CRM_Casereminder_DAO_CaseReminderType {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_OptionGroup
   */
  public static function retrieve(&$params, &$defaults) {
    $daoClassName = get_parent_class();
    $entity = new $daoClassName();
    $entity->copyValues($params);
    if ($entity->find(TRUE)) {
      CRM_Core_DAO::storeValues($entity, $defaults);
      return $entity;
    }
    return NULL;
  }

  public static function getDowOptions() {
    return [
      'sunday' => E::ts('Sunday'),
      'monday' => E::ts('Monday'),
      'tuesday' => E::ts('Tuesday'),
      'wednesday' => E::ts('Wednesday'),
      'thursday' => E::ts('Thursday'),
      'friday' => E::ts('Friday'),
      'saturday' => E::ts('Saturday'),
    ];
  }

  public static function getRecipientOptions() {
    $caseRoleValuesByTypeId = CRM_Casereminder_Util_Caseremindertype::getCaseRoleValuesByTypeId();
    $buildOptions = [
      '-1' => E::ts('Case client'),
    ];
    $relationshipTypeGet = _casereminder_civicrmapi('relationshipType', 'get', [
      'is_active' => 1,
      'options' => [
        'sort' => 'label_a_b ASC',
        'limit' => 0,
      ],
    ]);
    $coreOptions = [];
    foreach ($relationshipTypeGet['values'] as $relationshipType) {
      $coreOptions[$relationshipType['id']] = E::ts('Role') . ": {$relationshipType['label_a_b']} / {$relationshipType['label_b_a']}";
    }

    $buildOptions += CRM_Casereminder_Util_Caseremindertype::filterOptionsByCaseOptions($coreOptions, $caseRoleValuesByTypeId);
    return $buildOptions;
  }

}

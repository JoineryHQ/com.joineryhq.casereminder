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

}

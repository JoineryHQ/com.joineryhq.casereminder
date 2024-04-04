<?php

/**
 * Static utility methods for caseremindertype entity
 *
 */
class CRM_Casereminder_Util_Caseremindertype {

  /**
   * Get an array of all active case type definitions, keyed by case type id.
   */
  public static function getCaseTypeDefinitions() {
    static $caseTypeDefinitions;
    if (!isset($caseTypeDefinitions)) {
      $caseTypeDefinitions = [];
      $caseTypeGet = _casereminder_civicrmapi('caseType', 'get', [
        'sequential' => 1,
        'return' => ["definition"],
        'is_active' => TRUE,
        'options' => ['limit' => 0],
      ]);
      foreach ($caseTypeGet['values'] as $value) {
        $caseTypeDefinitions[$value['id']] = $value['definition'];
      }
    }
    return $caseTypeDefinitions;
  }

  /**
   * Get an array of relationship type IDs that are configured for use as roles
   * by each active case type; each case type also gets the '-1' relations;hip
   * type, which represents the "case client".
   *
   * @return Array.
   *   Keyed by case type id, containing an array of relationship type ids.
   *   Example: return [
   *     '1' => [-1, 12, 15],
   *     '2' => [-1, 12, 16],
   *   ];
   */
  public static function getCaseRoleValuesByTypeId() {
    static $caseRoleValuesByTypeId;
    if (!isset($caseRoleValuesByTypeId)) {
      $caseRoleValuesByTypeId = [];

      $relationshipTypeGet = $result = _casereminder_civicrmapi('RelationshipType', 'get', [
        'sequential' => 1,
        'option_group_id' => "case_status",
        'is_active' => TRUE,
      ]);
      // Create an array of all relationship types, keyed by both name_b_a and name_a_b,
      // because we can't know which one is used for in the caseType definition 'roles'.
      $relationshipTypesByName =
        CRM_Utils_Array::rekey($relationshipTypeGet['values'], 'name_b_a')
        + CRM_Utils_Array::rekey($relationshipTypeGet['values'], 'name_a_b');

      $caseTypeDefinitions = self::getCaseTypeDefinitions();

      foreach ($caseTypeDefinitions as $caseTypeId => $caseTypeDefinition) {
        // Start with -1 ("Case client"), which is a "fake" role that's valid for all case types.
        $caseRoleValuesByTypeId[$caseTypeId] = ['-1'];
        foreach ($caseTypeDefinition['caseRoles'] as $caseRole) {
          $roleName = $caseRole['name'];
          $caseRoleValuesByTypeId[$caseTypeId][] = $relationshipTypesByName[$roleName]['id'];
        }
      }
    }
    return $caseRoleValuesByTypeId;
  }

  /**
   * Get an array of values for option_values in option_group "case_status", grouped
   * as they are configured for use as case statuses by each active case type.
   *
   * @return Array
   *   Keyed by case type id, containing an array of case_status values.
   *   Example: return [
   *     '1' => [1, 2],
   *     '2' => [1, 2, 3],
   *   ];
   */
  public static function getCaseStatusValuesByTypeId() {
    static $caseStatusValuesByTypeId;
    if (!isset($caseStatusValuesByTypeId)) {
      $caseStatusValuesByTypeId = [];

      $caseStatusGet = $result = _casereminder_civicrmapi('OptionValue', 'get', [
        'sequential' => 1,
        'option_group_id' => "case_status",
        'is_active' => TRUE,
      ]);
      $caseStatusesByName = CRM_Utils_Array::rekey($caseStatusGet['values'], 'name');

      // A case type may have no specific statuses configured, which means it will
      // use the default options: all available statuses.
      $defaultStatuses = array_values(CRM_Utils_Array::collect('value', $caseStatusesByName));

      $caseTypeDefinitions = CRM_Casereminder_Util_Caseremindertype::getCaseTypeDefinitions();
      foreach ($caseTypeDefinitions as $caseTypeId => $caseTypeDefinition) {
        $caseStatusValuesByTypeId[$caseTypeId] = [];
        if (isset($caseTypeDefinition['statuses']) && is_array($caseTypeDefinition['statuses'])) {
          // If this case type has statuses defined, use those.
          foreach ($caseTypeDefinition['statuses'] as $statusName) {
            $caseStatusValuesByTypeId[$caseTypeId][] = $caseStatusesByName[$statusName]['value'];
          }
        }
        else {
          // Otherwise, use the default options.
          $caseStatusValuesByTypeId[$caseTypeId] = $defaultStatuses;
        }
      }
    }
    return $caseStatusValuesByTypeId;
  }

  /**
   * For any given set of options, and a given set of such options which are actually
   * in use for various case_types: strip out any options that are not in use and
   * only return the list of options which are actually used.
   *
   * @param array $options Full list of all options (e.g. all case_status values)
   * @param array $valuesByCaseTypeId List of options in-use by each active case
   *   type, keyed by case_type_id. E.g. return value of self::getCaseRoleValuesByTypeId()
   * @return array All value from $options which are represented among the members of $valuesByCaseTypeId
   */
  public static function filterOptionsByCaseOptions($options, $valuesByCaseTypeId) {
    $caseValues = [];
    foreach ($valuesByCaseTypeId as $values) {
      $caseValues += array_intersect_key($options, array_flip($values));
    }
    return $caseValues;
  }

}

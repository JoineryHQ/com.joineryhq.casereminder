<?php
use CRM_Casereminder_ExtensionUtil as E;

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of Listener
 *
 * @author as
 */
class CRM_Casereminder_Listener {

  const tokenPrefix = 'CaseReminder_Case';

  /**
   * Listener on 'civi.token.list' event, connected in casereminder_civicrm_container().
   *
   * @staticvar boolean $calledOnce
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   */
  public static function tokenRegister(\Civi\Token\Event\TokenRegisterEvent $e) {
    $caseFields = self::getCaseFieldsForTokens();
    foreach ($caseFields as $caseField) {
      $label = $caseField['html']['label'] ?? $caseField['label'] ?? $caseField['title'];
      if ($caseField['custom_field_id'] ?? FALSE) {
        $label = E::ts('Custom Field: ') . $label;
      }
      $e->entity(self::tokenPrefix)->register($caseField['name'], $label);
    }
  }

  /**
   * Listener on 'civi.token.eval' event, connected in casereminder_civicrm_container().
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   */
  public static function tokenEval(\Civi\Token\Event\TokenValueEvent $e) {
    if ($caseId = ($statics = Civi::$statics['casreminder_token_case_id'] ?? FALSE)) {
      $rows = $e->getRows();
      $tokens = $e->getTokenProcessor()->getMessageTokens();
      $myTokens = $tokens[self::tokenPrefix] ?? [];
      if (!empty($myTokens)) {
        $caseGet = _casereminder_civicrmapi('case', 'get', ['id' => $caseId]);
        if ($case = reset($caseGet['values'])) {
          foreach ($rows as $row) {
            $row->format('text/html');
            foreach ($myTokens as $tokenName) {
              $tokenValue = self::getCaseTokenValue($case, $tokenName);
              $row->tokens(self::tokenPrefix, $tokenName, $tokenValue);
            }
          }
        }
      }
      Civi::$statics['casreminder_token_case_id'] = NULL;
    }
  }

  /**
   * Utility method to fetch fields on case entity, for use as tokens.
   * @return Array
   */
  private static function getCaseFieldsForTokens() {
    // Static to avoid redundant fetching.
    Civi::$statics[__METHOD__] = [];
    if (empty(Civi::$statics[__METHOD__])) {
      $caseGetFields = civicrm_api3('Case', 'getfields', [
        'api_action' => "get",
        'options' => ['limit' => 0],
      ]);
      $unsupportedFieldNames = [
        'activity_id',
        'tag_id',
        'contact_id',
        'details',
      ];
      foreach ($caseGetFields['values'] as $caseField) {
        if (in_array($caseField['name'], $unsupportedFieldNames)) {
          continue;
        }
        Civi::$statics[__METHOD__][$caseField['name']] = $caseField;
      }
    }
    return Civi::$statics[__METHOD__];
  }

  /**
   * Utiltity method to determine token value, including pseudoconstant matching.
   * @param Array $case As provided by api, e.g. case.getSingle.
   * @param String $tokenName Token name / field name.
   * @return mixed
   */
  private static function getCaseTokenValue($case, $tokenName) {
    $tokenValue = '';
    $caseFields = self::getCaseFieldsForTokens();
    if ($case[$tokenName] ?? '') {
      $tokenValue = $case[$tokenName];
      if ($caseFields[$tokenName]['pseudoconstant'] ?? '') {
        $pseudoConstantOptions = CRM_Case_BAO_Case::buildOptions($tokenName);
        $tokenValue = $pseudoConstantOptions[$tokenValue];
      }
    }
    return $tokenValue;
  }

}

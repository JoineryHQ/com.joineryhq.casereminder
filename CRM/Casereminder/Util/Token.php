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
class CRM_Casereminder_Util_Token {

  const tokenPrefix = 'CaseReminder_Case';

  /**
   * Listener on 'civi.token.list' event, connected in casereminder_civicrm_container().
   *
   * @staticvar boolean $calledOnce
   * @param \Civi\Token\Event\TokenRegisterEvent $e
   */
  public static function listenTokenList(\Civi\Token\Event\TokenRegisterEvent $e) {
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
  public static function listenTokenEval(\Civi\Token\Event\TokenValueEvent $e) {
    // Tokens provided by this extension are only supported when sending
    // a caseReminder, which is only sent to one recipient at a time. Therefore
    // count($rows) is always 1.
    $rows = $e->getRows();
    if (iterator_count($rows) != 1) {
      return;
    }
    $messageTokenNames = $e->getTokenProcessor()->getMessageTokens();
    $myTokenNames = $messageTokenNames[self::tokenPrefix] ?? [];
    if (empty($myTokenNames)) {
      return;
    }
    $tokenValues = self::getTokenValuesNow($myTokenNames);
    foreach ($rows as $row) {
      $row->format('text/html');
      foreach ($tokenValues as $tokenName => $tokenValue) {
        $row->tokens(self::tokenPrefix, $tokenName, $tokenValue);
      }
    }
  }

  /**
   * Get values for the given tokenNames according to the current state token
   * environment (reference self::getTokenEnvCaseId)
   *
   * @param array $tokenNames Names of tokens to be populated
   *
   * @return array of token values, keyed to token names.
   */
  public static function getTokenValuesNow($tokenNames) {
    $tokenValuesNow = [];
    if (!empty($tokenNames)) {
      if ($caseId = self::getTokenEnvCaseId()) {
        $caseGet = _casereminder_civicrmapi('case', 'get', ['id' => $caseId]);
        if ($case = reset($caseGet['values'])) {
          foreach ($tokenNames as $tokenName) {
            $tokenValuesNow[$tokenName] = self::getCaseTokenValue($case, $tokenName);
          }
        }
      }
    }
    return $tokenValuesNow;
  }

  /**
   * Utility method to fetch fields on case entity, for use as tokens.
   * @return Array
   */
  private static function getCaseFieldsForTokens() {
    // Static to avoid redundant fetching.
    Civi::$statics[__METHOD__]['caseFields'] = [];
    if (empty(Civi::$statics[__METHOD__]['caseFields'])) {
      $caseGetFields = _casereminder_civicrmapi('Case', 'getfields', [
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
        Civi::$statics[__METHOD__]['caseFields'][$caseField['name']] = $caseField;
      }
    }
    return Civi::$statics[__METHOD__]['caseFields'];
  }

  /**
   * Utility method to determine token value, including pseudoconstant matching
   * and boolean "Yes/No" formatting
   * @param Array $case As provided by api, e.g. case.getSingle.
   * @param String $tokenName Token name / field name.
   * @return mixed
   */
  private static function getCaseTokenValue($case, $tokenName) {
    $tokenValue = '';
    $caseFields = self::getCaseFieldsForTokens();
    $tokenValue = ($case[$tokenName] ?? '');
    if ($caseField = ($caseFields[$tokenName] ?? '')) {
      if ($caseField['pseudoconstant'] ?? '') {
        $pseudoConstantOptions = CRM_Case_BAO_Case::buildOptions($tokenName);
        $tokenValue = $pseudoConstantOptions[$tokenValue];
      }
      elseif ($caseField['type'] == CRM_Utils_Type::T_BOOLEAN) {
        $tokenValue = ($tokenValue ? E::ts('Yes') : E::ts('No'));
      }
      elseif ($caseField['type'] == CRM_Utils_Type::T_TIMESTAMP) {
        $tokenValue = CRM_Utils_Date::customFormat($tokenValue);
      }
    }
    return $tokenValue;
  }

  public static function setTokenEnvCaseId($value) {
    Civi::$statics[__CLASS__]['tokenEnvVars']['caseId'] = $value;
  }

  public static function getTokenEnvCaseId() {
    return (Civi::$statics[__CLASS__]['tokenEnvVars']['caseId'] ?? NULL);
  }

}

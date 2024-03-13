<?php

require_once 'casereminder.civix.php';

use CRM_Casereminder_ExtensionUtil as E;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_container().
 */
function casereminder_civicrm_container(ContainerBuilder $container) {
  $container->findDefinition('dispatcher')->addMethodCall('addListener', ['civi.token.list', 'CRM_Casereminder_Util_Token::listenTokenList'])->setPublic(TRUE);
  $container->findDefinition('dispatcher')->addMethodCall('addListener', ['civi.token.eval', 'CRM_Casereminder_Util_Token::listenTokenEval'])->setPublic(TRUE);
}

/**
 * Implements hook_civicrm_permission().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission/
 */
function casereminder_civicrm_permission(&$permissions): void {
  $permissions['administer_casereminder'] = [
    'label' => E::ts('Case Reminders: administer Case Reminders'),
    'description' => E::ts('Administer all settings and configurations for Case Reminders'),
  ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function casereminder_civicrm_config(&$config): void {
  _casereminder_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function casereminder_civicrm_install(): void {
  _casereminder_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function casereminder_civicrm_enable(): void {
  _casereminder_civix_civicrm_enable();
}

/**
 * Log CiviCRM API errors to CiviCRM log.
 */
function _casereminder_log_api_error(CiviCRM_API3_Exception $e, $entity, $action, $contextMessage = NULL, $params) {
  $message = "CiviCRM API Error '{$entity}.{$action}': " . $e->getMessage() . '; ';
  $message .= "API parameters when this error happened: " . json_encode($params) . '; ';
  $bt = debug_backtrace();
  $error_location = "{$bt[1]['file']}::{$bt[1]['line']}";
  $message .= "Error API called from: $error_location";
  CRM_Core_Error::debug_log_message($message);

  $casereminderLogMessage = $message;
  if ($contextMessage) {
    $casereminderLogMessage .= "; Context: $contextMessage";
  }
}

/**
 * CiviCRM API wrapper. Wraps with try/catch, redirects errors to log, saves
 * typing.
 *
 * @param string $entity as in civicrm_api3($ENTITY, ..., ...)
 * @param string $action as in civicrm_api3(..., $ACTION, ...)
 * @param array $params as in civicrm_api3(..., ..., $PARAMS)
 * @param string $contextMessage Additional message for inclusion in log upon any failures.
 * @param bool $silence_errors If TRUE, throw any exceptions we catch; otherwise don't.
 *
 * @return Array result of civicrm_api3()
 * @throws CiviCRM_API3_Exception
 */
function _casereminder_civicrmapi($entity, $action, $params, $contextMessage = NULL, $silence_errors = FALSE) {
  try {
    $result = civicrm_api3($entity, $action, $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    _casereminder_log_api_error($e, $entity, $action, $contextMessage, $params);
    if (!$silence_errors) {
      throw $e;
    }
  }

  return $result;
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function casereminder_civicrm_navigationMenu(&$menu) {
  _casereminder_get_max_navID($menu, $max_navID);
  $item = [
    'label' => E::ts('Case Reminder Types'),
    'name' => 'CaseReminderTypes',
    'url' => 'civicrm/admin/casereminder/types?reset=1',
    'permission' => 'administer_casereminder',
    'operator' => 'AND',
    'separator' => NULL,
    'navId' => ++$max_navID,
  ];
  _casereminder_civix_insert_navigation_menu($menu, 'Administer/CiviCase', $item);
  _casereminder_civix_navigationMenu($menu);
}

/**
 * For an array of menu items, recursively get the value of the greatest navID
 * attribute.
 * @param <type> $menu
 * @param <type> $max_navID
 */
function _casereminder_get_max_navID(&$menu, &$max_navID = NULL) {
  foreach ($menu as $id => $item) {
    if (!empty($item['attributes']['navID'])) {
      $max_navID = max($max_navID, $item['attributes']['navID']);
    }
    if (!empty($item['child'])) {
      _casereminder_get_max_navID($item['child'], $max_navID);
    }
  }
}

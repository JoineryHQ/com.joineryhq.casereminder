<?php

require_once 'casereminder.civix.php';

use CRM_Casereminder_ExtensionUtil as E;

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

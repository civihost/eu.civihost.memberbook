<?php

require_once 'memberbook.civix.php';

use CRM_Memberbook_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function memberbook_civicrm_config(&$config): void {
  _memberbook_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function memberbook_civicrm_install(): void {
  _memberbook_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function memberbook_civicrm_enable(): void {
  _memberbook_civix_civicrm_enable();
}

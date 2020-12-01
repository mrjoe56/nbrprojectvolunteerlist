<?php

require_once 'nbrprojectvolunteerlist.civix.php';
use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Implements hook_civicrm_post
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function nbrprojectvolunteerlist_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == "Activity" && $op == "create") {
    // only if email or PDF activity type id
    $emailType = Civi::service('nbrBackbone')->getEmailActivityTypeId();
    $pdfType = Civi::service('nbrBackbone')->getLetterActivityTypeId();
    if ($objectRef->activity_type_id == $emailType || $objectRef->activity_type_id == $pdfType) {
      if (CRM_Core_Transaction::isActive()) {
        CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, 'CRM_Nbrprojectvolunteerlist_NbrParticipation::fileActivityOnCases', [$objectId, $objectRef->activity_type_id]);
      }
      else {
        CRM_Nbrprojectvolunteerlist_NbrParticipation::fileActivityOnCases($objectId, $objectRef->activity_type_id);
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function nbrprojectvolunteerlist_civicrm_buildForm($formName, &$form) {  # jb2
  if ($form instanceof CRM_Contact_Form_Task_Email) {
    $nbrParticipation = new CRM_Nbrprojectvolunteerlist_NbrParticipation();
    $nbrParticipation->emailBuildForm($form);
  }
  if ($form instanceof CRM_Contact_Form_Task_PDF) {
    $nbrParticipation = new CRM_Nbrprojectvolunteerlist_NbrParticipation();
    $nbrParticipation->pdfBuildForm($form);
  }
  if ($form instanceof CRM_Nbrprojectvolunteerlist_Form_Task_InviteByPdf) {
    $nbrParticipation = new CRM_Nbrprojectvolunteerlist_NbrParticipation();
    $nbrParticipation->pdfInviteBuildForm($form);
  }
}


/**
 * Implements hook_civicrm_searchTasks().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchTasks
 */
function nbrprojectvolunteerlist_civicrm_searchTasks($objectType, &$tasks) {
  if (strtolower($objectType) == "contact") {
    // todo check if there is not an easier way to determine if I am in the correct custom search
    CRM_Nbrprojectvolunteerlist_SearchTasks::processSearchTasksHook($tasks);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function nbrprojectvolunteerlist_civicrm_config(&$config) {
  _nbrprojectvolunteerlist_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function nbrprojectvolunteerlist_civicrm_xmlMenu(&$files) {
  _nbrprojectvolunteerlist_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function nbrprojectvolunteerlist_civicrm_install() {
  _nbrprojectvolunteerlist_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function nbrprojectvolunteerlist_civicrm_postInstall() {
  _nbrprojectvolunteerlist_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function nbrprojectvolunteerlist_civicrm_uninstall() {
  _nbrprojectvolunteerlist_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function nbrprojectvolunteerlist_civicrm_enable() {
  _nbrprojectvolunteerlist_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function nbrprojectvolunteerlist_civicrm_disable() {
  _nbrprojectvolunteerlist_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function nbrprojectvolunteerlist_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _nbrprojectvolunteerlist_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function nbrprojectvolunteerlist_civicrm_managed(&$entities) {
  _nbrprojectvolunteerlist_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function nbrprojectvolunteerlist_civicrm_caseTypes(&$caseTypes) {
  _nbrprojectvolunteerlist_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function nbrprojectvolunteerlist_civicrm_angularModules(&$angularModules) {
  _nbrprojectvolunteerlist_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function nbrprojectvolunteerlist_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _nbrprojectvolunteerlist_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function nbrprojectvolunteerlist_civicrm_entityTypes(&$entityTypes) {
  _nbrprojectvolunteerlist_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function nbrprojectvolunteerlist_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function nbrprojectvolunteerlist_civicrm_navigationMenu(&$menu) {
  _nbrprojectvolunteerlist_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _nbrprojectvolunteerlist_civix_navigationMenu($menu);
} // */

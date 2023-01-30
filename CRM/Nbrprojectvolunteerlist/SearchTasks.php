<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to add the search tasks to the search form
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 6 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_SearchTasks {

  private $_csId = NULL;

  /**
   * CRM_Nbrprojectvolunteerlist_SearchTasks constructor.
   */
  public function __construct() {
    try {
      $this->_csId = (int) civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "custom_search",
        'name' => "CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList",
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Could not find a custom search with class '));
    }
  }

  /**
   * Method to process the search tasks hook
   *
   * @param $tasks
   * @throws CRM_Core_Exception
   */
  public static function processSearchTasksHook(&$tasks) {
    $loadNbrTaskList = self::loadNbrTaskList();
    if ($loadNbrTaskList) {
      self::setProjectVolunteerListTasks($tasks);
    }
    else {
      Civi::settings()->set(CRM_Nbrprojectvolunteerlist_Utils::getQfKeySettingName(), "");
    }
  }

  /**
   * Method to check if the nbr task list should be loaded
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  private static function loadNbrTaskList() {
    $st = new CRM_Nbrprojectvolunteerlist_SearchTasks();
    $csId = (int) CRM_Utils_Request::retrieveValue('csid', "Integer");
    if (isset($_GET['q'])) {
      $q = $_GET['q'];
    }
    if (!empty($csId)) {
      if ($csId == $st->_csId) {
        // get qfKey, if no qfKey clear
        $qfKey = CRM_Utils_Request::retrieveValue('qfKey', "String");
        if ($qfKey) {
          // store key if we are not in display mode
          $display = CRM_Utils_Request::retrieveValue('_qf_Custom_display', "String");
          if (!$display) {
            Civi::settings()->set(CRM_Nbrprojectvolunteerlist_Utils::getQfKeySettingName(), $qfKey);
            return TRUE;
          }
        }
        else {
          // clear qfKey
          Civi::settings()->set(CRM_Nbrprojectvolunteerlist_Utils::getQfKeySettingName(), "");
          return TRUE;
        }
      }
    }
    // if custom search or display and key correct, return TRUE
    if ($q == 'civicrm/contact/search/custom') {
      $qfKey = CRM_Utils_Request::retrieveValue('qfKey', "String");
      $checkKey = Civi::settings()->get(CRM_Nbrprojectvolunteerlist_Utils::getQfKeySettingName());
      if ($checkKey == $qfKey) {
        return TRUE;
      }
    }
    // if one of specific actions, return TRUE
    $taskClasses = ['InviteByEmail', 'ChangeStudyStatus', 'InviteBulk', 'BulkMailing', 'AddFollowUp'];
    foreach ($taskClasses as $taskClass) {
      $checkDisplay = CRM_Utils_Request::retrieveValue('_qf_' . $taskClass . '_display', 'String');
      $checkNext = CRM_Utils_Request::retrieveValue('_qf_' . $taskClass . '_next', 'String');
      $checkBack = CRM_Utils_Request::retrieveValue('_qf_' . $taskClass . '_back', 'String');
      if ($checkDisplay || $checkNext || $checkBack) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Method to set the tasks for the project volunteer list
   *
   * @param $tasks
   */
  private static function setProjectVolunteerListTasks(&$tasks) {
    $formValues = self::getFormValues();
    if (!isset($formValues['study_id']) || empty($formValues['study_id'])) {
      $studyId = (int) CRM_Utils_Request::retrieveValue('sid', "Integer");
      if (!$studyId) {
        $studyId = (int) CRM_Utils_Request::retrieveValue('study_id', "Integer");
      }
    } else {
      $studyId = (int) $formValues['study_id'];
    }
    $includeInviteTasks = FALSE;
    if (empty($studyId)) {
      CRM_Core_Session::setStatus(E::ts('No Study ID provided'), '', 'error');
    } else {
      try {
        $studyStatus = civicrm_api3('Campaign', 'getvalue', [
          'return' => 'status_id',
          'id' => $studyId,
        ]);
        $invitateStatuses = explode(",", Civi::settings()->get('nbr_invite_campaign_status'));
        if (in_array($studyStatus, $invitateStatuses) && (CRM_Nihrbackbone_NbrStudy::isFaceToFace($studyId) || CRM_Nihrbackbone_NbrStudy::isOnline($studyId))) {
          $includeInviteTasks = TRUE;
        }
      } catch (\Exception $e) {
        CRM_Core_Session::setStatus(E::ts('No Study found'), '', 'error');
      }
    }
    $nbrTasks = [];
    if ($includeInviteTasks) {
      $nbrTasks[] = [
        'title' => "Invite Volunteer(s) by Email (max. 50)",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_InviteByEmail",
      ];
      $nbrTasks[] = [
        'title' => "Invite Volunteer(s) by Bulk Mail (50+)",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_InviteBulk",
      ];
      $nbrTasks[] = [
        'title' => "Invite Volunteer(s) by PDF",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_InviteByPdf",
      ];
    }
    $nbrTasks[] = [
      'title' => "Send bulk mailing to Volunteer(s) (50+)",
      'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_BulkMailing",
    ];
    $nbrTasks[] = [
      'title' => "Change Status on Study for Volunteer(s)",
      'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_ChangeStudyStatus",
    ];
    $nbrTasks[] = [
      'title' => "Export CSV for External Researcher(s)",
      'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_ExportExternal",
    ];
    $nbrTasks[] = [
      'title' => "Export CSV with Selected Fields",
      'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_ExportSelect",
    ];
    $nbrTasks[] = [
      'title' => "Add Follow Up Activity",
      'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_AddFollowUp",
    ];
    foreach ($nbrTasks as $nbrTask) {
      $tasks[] = $nbrTask;
    }
    $keepTasks = [
      "CRM_Contact_Form_Task_AddToGroup",
      "CRM_Contact_Form_Task_RemoveFromGroup",
      "CRM_Contact_Form_Task_AddToTag",
      "CRM_Contact_Form_Task_RemoveFromTag",
      "CRM_Contact_Form_Task_PDF",
      "CRM_Contact_Form_Task_Email",
    ];
    foreach ($tasks as $taskId => $task) {
      if (isset($task['class'])) {
        if (!is_array($task['class'])) {
          if (!in_array($task['class'], $keepTasks) && strpos($task['class'],'CRM_Nbrprojectvolunteerlist') === FALSE) {
            unset($tasks[$taskId]);
          }
        }
        else {
          foreach ($task['class'] as $className) {
            if (!in_array($className, $keepTasks) && strpos($className, 'CRM_Nbrprojectvolunteerlist') === FALSE) {
              unset($tasks[$taskId]);
            }
          }
        }
      }
    }
  }

  /**
   * Returns the form values.
   *
   * This is a little hack, we have to retrieve it from the session and to do so
   * we have to construct a name for the scope. Which consists of the Controller class name
   * and the qfKey.
   * The controller class name is assumed to be 'CRM_Contact_Controller_Search'. Not sure
   * whether it is the right class name is all circumstances.
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
   */
  private static function getFormValues() {
    $qfKey = CRM_Utils_Request::retrieveValue('qfKey', "String");
    $scope = 'CRM_Contact_Controller_Search_'.$qfKey;
    return CRM_Core_Session::singleton()->get('formValues', $scope);
  }

  /**
   * Method to get the custom search id
   *
   * @return int|null
   */
  public function getCsId() {
    return $this->_csId;
  }
}


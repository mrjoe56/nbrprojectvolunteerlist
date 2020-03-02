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
   * @return array $tasks
   */
  public static function processSearchTasksHook(&$tasks) {
    $loadNbrTaskList = self::loadNbrTaskList();
    if ($loadNbrTaskList) {
      self::setProjectVolunteerListTasks($tasks);
    }
    else {
      Civi::settings()->set('nbr_cs_volunteerlist_qfKey', "");
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
            Civi::settings()->set('nbr_cs_volunteerlist_qfKey', $qfKey);
            return TRUE;
          }
        }
        else {
          // clear qfKey
          Civi::settings()->set('nbr_cs_volunteerlist_qfKey', "");
          return TRUE;
        }
      }
    }
    // if custom search or display and key correct, return TRUE
    if ($q == 'civicrm/contact/search/custom') {
      $qfKey = CRM_Utils_Request::retrieveValue('qfKey', "String");
      $checkKey = Civi::settings()->get('nbr_cs_volunteerlist_qfKey');
      if ($checkKey == $qfKey) {
        return TRUE;
      }
    }
    // if one of specific actions, return TRUE
    $taskClasses = ['InviteByEmail', 'InviteByPdf', 'ChangeStudyStatus'];
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
    $nbrTasks = [
      [
        'title' => "Invite Volunteer(s) by Email",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_InviteByEmail",
      ],
      [
        'title' => "Invite Volunteer(s) by Letter",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_InviteByPdf",
      ],
      [
        'title' => "Change Status on Study for Volunteer(s)",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_ChangeStudyStatus",
      ],
      [
        'title' => "Export CSV for External Researcher(s)",
        'class' => "CRM_Nbrprojectvolunteerlist_Form_Task_ExportExternal",
      ],
    ];
    foreach ($nbrTasks as $nbrTask) {
      $tasks[] = $nbrTask;
    }
    $keepTasks = [
      "CRM_Contact_Form_Task_AddToGroup",
      "CRM_Contact_Form_Task_RemoveFromGroup",
      "CRM_Contact_Form_Task_AddToTag",
      "CRM_Contact_Form_Task_RemoveFromTag",
      "CRM_Contact_Form_Task_Email",
      "CRM_Contact_Form_Task_PDF",
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
}


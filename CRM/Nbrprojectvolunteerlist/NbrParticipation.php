<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class for participation processing specific to the study participant management screen
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 19 Oct 2020
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_NbrParticipation {

  /**
   * Method to process the build form hook for email
   * (only when coming from the MSP screen)
   * @param $form
   */
  public function emailBuildForm(&$form) {
    // only for custom searches
    $context = $form->getVar('_context');
    if ($context == "custom") {
      $searchFormValues = $form->controller->exportValues('Custom');
      // only if search form = Manage Study Participation
      $msp = new CRM_Nbrprojectvolunteerlist_SearchTasks();
      $mspCsId = $msp->getCsId();
      if (isset($searchFormValues['csid']) && (int) $searchFormValues['csid'] == $mspCsId) {
        if (isset($searchFormValues['study_id'])) {
          $studyId = (int) $searchFormValues['study_id'];
          if ($studyId) {
            $contactIds = $form->getVar('_contactIds');
            $caseIds = [];
            foreach ($contactIds as $contactId) {
              $caseIds[] = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
            }
            if (!empty($caseIds)) {
              $session = CRM_Core_Session::singleton();
              $session->nbr_email_case_ids = $caseIds;
            }
          }
        }
      }
    }
  }

  public static function fileEmailOnCases($activityId) {
    // only if case ids in session (put there in buildForm of email
    // task if iniated from MSP screen
    $session = CRM_Core_Session::singleton();
    if (isset($session->nbr_email_case_ids)) {
      foreach ($session->nbr_email_case_ids as $caseId) {
        $insert = "INSERT INTO civicrm_case_activity (case_id, activity_id) VALUES(%1, %2)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [(int) $caseId, "Integer"],
          2 => [(int) $activityId, "Integer"],
        ]);
        unset($session->nbr_email_case_ids);
      }
    }
  }
}


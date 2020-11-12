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
              $session->nbr_activity_case_ids = $caseIds;
            }
          }
        }
      }
    }
  }

  /**
   * Method to process the build form hook for pdf
   * (only when coming from the MSP screen)
   * @param $form
   */
  public function pdfBuildForm(&$form) {
    $searchFormValues = $form->controller->exportValues();
    // only if search form = Manage Study Participation
    $msp = new CRM_Nbrprojectvolunteerlist_SearchTasks();
    $mspCsId = $msp->getCsId();
    if (isset($searchFormValues['csid']) && (int) $searchFormValues['csid'] == $mspCsId) {
      // add invite flag
      $form->add('advcheckbox', 'is_nbr_invite', E::ts('Is this a study invite PDF?'), [], FALSE);
      $form->removeElement('campaign_id');
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nbrprojectvolunteerlist/Form/Task/InviteByPdf.tpl',]);
      if (isset($searchFormValues['study_id'])) {
        $studyId = (int) $searchFormValues['study_id'];
        $form->add('hidden', 'study_id');
        $form->setDefaults(['study_id' => $studyId]);
        if ($studyId) {
          $contactIds = $form->getVar('_contactIds');
          $caseIds = [];
          foreach ($contactIds as $contactId) {
            $caseIds[] = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
          }
          if (!empty($caseIds)) {
            $session = CRM_Core_Session::singleton();
            $session->nbr_activity_case_ids = $caseIds;
          }
        }
      }
    }
  }

  /**
   * Method to file email activities on cases
   *
   * @param $activityId
   * @param $activityTypeId
   */
  public static function fileActivityOnCases($activityId, $activityTypeId) {
    $caseIds = [];
    $session = CRM_Core_Session::singleton();
    // only if case ids in session (put there in buildForm of email task if initiated from MSP screen
    if ($activityTypeId == Civi::service('nbrBackbone')->getEmailActivityTypeId()) {
      if (isset($session->nbr_activity_case_ids)) {
        $caseIds = $session->nbr_activity_case_ids;
        unset($session->nbr_activity_case_ids);
      }
    }
    if ($activityTypeId == Civi::service('nbrBackbone')->getLetterActivityTypeId()) {
      $caseId = self::getPdfCaseAndInvite($activityId);
      if ($caseId) {
        $caseIds[] = $caseId;
      }
    }
    if (!empty($caseIds)) {
      foreach ($caseIds as $caseId) {
        $insert = "INSERT INTO civicrm_case_activity (case_id, activity_id) VALUES(%1, %2)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [(int) $caseId, "Integer"],
          2 => [(int) $activityId, "Integer"],
        ]);
      }
    }
  }

  /**
   * Method to retrieve caseIds for PDF Letter
   *
   * @param int $activityId
   * @return int
   * @throws CRM_Core_Exception
   */
  private static function getPdfCaseAndInvite($activityId) {
    $isInvite = CRM_Utils_Request::retrieveValue('is_nbr_invite', 'Boolean');
    $studyId = CRM_Utils_Request::retrieveValue('study_id', 'Integer');
    if ($isInvite == TRUE && $studyId) {
      // get target contact for activity
      $query = "SELECT contact_id FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2";
      $contactId = (int) CRM_Core_DAO::singleValueQuery($query, [
        1 => [(int) $activityId, "Integer"],
        2 => [Civi::service('nbrBackbone')->getTargetRecordTypeId(), "Integer"],
      ]);
      if ($contactId) {
        $caseId = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
        CRM_Nihrbackbone_NbrInvitation::addInviteActivity($caseId, $contactId, $studyId, "invite by letter");
        return $caseId;
      }
    }
    return FALSE;
  }
}


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
              $caseIds[$contactId] = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
            }
            if (!empty($caseIds)) {
              $session = CRM_Core_Session::singleton();
              $session->nbr_email_pdf_case_ids = $caseIds;
            }
          }
        }
      }
    }
  }

  /**
   * Method to process the build form hook for pdf
   * (only when coming from the MSP screen) -> add study id to form
   * @param $form
   */
  public function pdfBuildForm(&$form) {
    $searchFormValues = $form->controller->exportValues();
    // only if search form = Manage Study Participation
    $msp = new CRM_Nbrprojectvolunteerlist_SearchTasks();
    $mspCsId = $msp->getCsId();
    if (isset($searchFormValues['csid']) && (int) $searchFormValues['csid'] == $mspCsId) {
      if (isset($searchFormValues['study_id'])) {
        $studyId = (int) $searchFormValues['study_id'];
        $form->removeElement('campaign_id');
        $form->add('hidden', 'study_id');
        $form->setDefaults(['study_id' => $studyId]);
      }
    }
  }

  /**
   * Method to process the build form hook for pdf invite
   * (only when coming from the MSP screen)
   * @param $form
   */
  public function pdfInviteBuildForm(&$form) {
    $searchFormValues = $form->controller->exportValues();
    // only if search form = Manage Study Participation
    $msp = new CRM_Nbrprojectvolunteerlist_SearchTasks();
    $mspCsId = $msp->getCsId();
    if (isset($searchFormValues['csid']) && (int) $searchFormValues['csid'] == $mspCsId) {
      if (isset($searchFormValues['study_id'])) {
        $studyId = (int) $searchFormValues['study_id'];
        $contactIds = $form->getVar('_contactIds');
        $invalidIds = [];
        $invitedIds = [];
        $form->removeElement('campaign_id');
        $form->add('hidden', 'study_id');
        $form->setDefaults(['study_id' => $studyId]);
        $form->add('hidden', 'is_nbr_invite');
        $form->setDefaults(['is_nbr_invite' => TRUE]);
        if ($studyId) {
          $caseIds = [];
          foreach ($contactIds as $contactId) {
            $caseId = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
            $this->addInvalidVolunteer($contactId, $caseId, $invitedIds, $invalidIds, $caseIds);
          }
          if (!empty($caseIds)) {
            $session = CRM_Core_Session::singleton();
            $session->nbr_email_pdf_case_ids = $caseIds;
          }
          // remove all invalids from _contactIds and _componentIds in form object
          $resultIds = [];
          foreach ($invitedIds as $invitedId => $invitedData) {
            $resultIds[] = (string) $invitedId;
          }
          $form->setVar("_contactIds", $resultIds);
          $form->setVar("_componentIds", $resultIds);
          $form->assign('invalid_ids', $invalidIds);
          $form->assign('invited_ids', $invitedIds);
        }
      }
    }
  }

  /**
   * Method to check if volunteer is invalid for invite
   * (not eligible or status is not selected)
   *
   * @param $contactId
   * @param $caseId
   * @param $invitedIds
   * @param $invalidIds
   * @param $caseIds
   */
  private function addInvalidVolunteer($contactId, $caseId, &$invitedIds, &$invalidIds, &$caseIds) {
    $eligible = CRM_Nihrbackbone_NbrVolunteerCase::getCurrentEligibleStatus($caseId);
    $status = CRM_Nihrbackbone_NbrVolunteerCase::getCurrentStudyStatus($caseId);
    if ($eligible[0] == Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue() &&
      $status == Civi::service('nbrBackbone')->getSelectedParticipationStatusValue()) {
      $invitedIds[$contactId] = [
        'display_name' => CRM_Nihrbackbone_Utils::getContactName($contactId, 'display_name'),
        'study_status' => CRM_Nihrbackbone_Utils::getOptionValueLabel($status, CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId()),
        'eligible_status' => CRM_Nihrbackbone_Utils::getOptionValueLabel($eligible[0], CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId()),
      ];
      $caseIds[$contactId] = $caseId;
    }
    else {
      $invalidIds[$contactId] = [
        'display_name' => CRM_Nihrbackbone_Utils::getContactName($contactId, 'display_name'),
        'study_status' => CRM_Nihrbackbone_Utils::getOptionValueLabel($status, CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId()),
        'eligible_status' => CRM_Nihrbackbone_Utils::getOptionValueLabel($eligible[0], CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId()),
      ];
    }
  }

  /**
   * Method to file email activities on cases
   *
   * @param $activityId
   * @param $activityTypeId
   */
  public static function fileActivityOnCases($activityId, $activityTypeId, $invite = FALSE) {
    $caseIds = [];
    $session = CRM_Core_Session::singleton();
    if ($activityTypeId == Civi::service('nbrBackbone')->getEmailActivityTypeId() || $activityTypeId == Civi::service('nbrBackbone')->getLetterActivityTypeId()) {
      if (isset($session->nbr_email_pdf_case_ids)) {
        $caseIds = $session->nbr_email_pdf_case_ids;
      }
    }
    if (!empty($caseIds)) {
      foreach ($caseIds as $caseContactId => $caseId) {
        $insert = "INSERT INTO civicrm_case_activity (case_id, activity_id) VALUES(%1, %2)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [(int) $caseId, "Integer"],
          2 => [(int) $activityId, "Integer"],
        ]);
        // if PPF, check if invite processing required
        if ($activityTypeId == Civi::service('nbrBackbone')->getLetterActivityTypeId()) {
          $studyId = CRM_Utils_Request::retrieveValue('study_id', 'Integer');
          $isInvite = CRM_Utils_Request::retrieveValue('is_nbr_invite', 'Boolean');
          if ($studyId && $isInvite) {
            CRM_Nihrbackbone_NbrInvitation::addInviteActivity($caseId, $caseContactId, $studyId, "invite by letter");
          }
        }
      }
    }
  }
}


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
            foreach ($contactIds as $contactId) {
              $caseId = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($studyId, $contactId);
              $form->setVar('_caseId', $caseId);
            }
          }
        }
      }
    }
  }

}


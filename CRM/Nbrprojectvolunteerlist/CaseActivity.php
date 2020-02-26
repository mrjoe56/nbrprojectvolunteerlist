<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to do case activity processing for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_CaseActivity {

  /**
   * Method to create the a case activity for a study status change
   *
   * @param $caseIds
   */
  public static function addStatusChangeActivities($newStatusLabel, $caseIds) {
    $activityTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getChangedStudyStatusActivityTypeId();
    $optionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    foreach ($caseIds as $caseId => $caseStatus) {
      if ($activityTypeId) {
        $currentStatusLabel = CRM_Nihrbackbone_Utils::getOptionValueLabel($caseStatus, $optionGroupId);
        try {
          civicrm_api3('Activity', 'create', [
            'source_contact_id' => "user_contact_id",
            'activity_type_id' => $activityTypeId,
            'subject' => "Changed from status " . $currentStatusLabel . " to status " . $newStatusLabel,
            'case_id' => $caseId,
            'status_id' => "Completed",
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts('Could not create a change status activity for case ID ') . $caseId
            . E::ts(', error message from API Activity create: ') . $ex->getMessage());
        }
      }
    }
  }

}


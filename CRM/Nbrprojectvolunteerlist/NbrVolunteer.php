<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class for volunteer processing specific to the study participant management screen
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 8 Sep 2020
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_NbrVolunteer {

  /**
   * Method to classify volunteer as invalid (with reason) or to be invited
   *
   * @param string $type
   * @param object $dao
   * @param array $invalids
   * @param int $countInvalids
   * @param array $invited
   * @param int $countInvited
   */
  public function classifyVolunteer($type, $dao, &$invalids, &$countInvalids, &$invited, &$countInvited) {
    $volunteer = [
      'display_name' => $dao->display_name,
      'study_participant_id' => $dao->study_participant_id,
      'email' => $dao->email,
    ];
    $eligibleStatus = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id));
    $volunteer['eligible_status'] = $eligibleStatus;
    $inviteType = substr($type,0,6);
    $valid = TRUE;
    if ($inviteType == "invite") {
      // do not allow invite if participation status is excluded
      if ($dao->study_participation_status == Civi::service('nbrBackbone')->getExcludedParticipationStatusValue()) {
        $countInvalids++;
        $volunteer['reason'] = E::ts("Excluded");
        $invalids[$dao->contact_id] = $volunteer;
        $valid = FALSE;
      }
      // only allow invite if eligible
      elseif (!$this->isEligibleStatus($dao->eligible_status_id)) {
        $countInvalids++;
        $volunteer['reason'] = E::ts("Not eligible");
        $invalids[$dao->contact_id] = $volunteer;
        $valid = FALSE;
      }
    }
    // do not allow if email is empty unless invite pdf
    if (empty($dao->email) && $type != "invite_pdf") {
      $countInvalids++;
      $volunteer['reason'] = E::ts("Does not have an active primary email address");
      $invalids[$dao->contact_id] = $volunteer;
      $valid = FALSE;
    }
    // do not allow if deceased
    if (CRM_Nihrbackbone_NihrVolunteer::isDeceased($dao->contact_id)) {
      $countInvalids++;
      $volunteer['reason'] = E::ts("Deceased");
      $invalids[$dao->contact_id] = $volunteer;
      $valid = FALSE;
    }
    // do not allow if contact has no_email flag (if no invite pdf)
    if (!CRM_Nihrbackbone_NihrVolunteer::allowsEmail($dao->contact_id) && $type != "invite_pdf") {
      $countInvalids++;
      $volunteer['reason'] = E::ts("Does not want to be emailed");
      $invalids[$dao->contact_id] = $volunteer;
      $valid = FALSE;
    }
    // do not allow if invalid email (if not invite_pdf)
    if (!filter_var($dao->email, FILTER_VALIDATE_EMAIL) && $type != "invite_pdf") {
      $countInvalids++;
      $volunteer['reason'] = E::ts("Invalid email address");
      $invalids[$dao->contact_id] = $volunteer;
      $valid = FALSE;
    }
    // do not allow more than 50 if not bulk or not invite_pdf
    if ($type == "invite_email" && $countInvited >= 50) {
      $countInvalids++;
      $volunteer['reason'] = E::ts("Can not mail more than 50");
      $invalids[$dao->contact_id] = $volunteer;
      $valid = FALSE;
    }
    if ($valid) {
      $countInvited++;
      $invited[$dao->contact_id] = $volunteer;
    }
  }

  /**
   * Check if status is eligible
   *   *
   * @param $statusId
   * @return bool
   */
  private function isEligibleStatus($statusId) {
    if (empty($statusId)) {
      return FALSE;
    }
    $parts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $statusId);
    foreach ($parts as $key => $value) {
      if (empty($value)) {
        unset($parts[$key]);
      }
    }
    if (count($parts) == 1) {
      $singleStatus = implode("", $parts);
      if ($singleStatus == Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}


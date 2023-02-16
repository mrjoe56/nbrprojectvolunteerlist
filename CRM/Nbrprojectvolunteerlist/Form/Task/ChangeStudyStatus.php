<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the change status on study for volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 6 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_ChangeStudyStatus extends CRM_Contact_Form_Task {

  private $_countSelected = NULL;
  private $_countWarnings = NULL;
  private $_selected = [];
  private $_warnings = [];
  private $_studyId = NULL;
  private $_countProcessed = 0;

  /**
   * Method to get the data for the selected contact IDs
   *
   */
  private function getSelectedData() {
    $this->_selected = [];
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $ovsOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnpd." . $studyParticipantColumn . " AS study_participant_id,
        cvnpd." . $eligiblesColumn . " AS eligible_status_id, ovs.label AS study_status
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_option_value AS ovs ON cvnpd." . $statusColumn. " = ovs.value AND ovs.option_group_id = " . $ovsOptionGroupId . "
        WHERE cvnpd." . $studyColumn. " = %1 AND cas.is_deleted = %2 AND vol.id IN (";
    $queryParams = [
      1 => [(int)$this->_studyId, "Integer"],
      2 => [0, "Integer"],
    ];
    $i = 2;
    CRM_Nbrprojectvolunteerlist_Utils::addContactIdsToQuery($i, $this->_contactIds, $query, $queryParams);
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $volunteer = [
        'display_name' => $dao->display_name,
        'study_participant_id' => $dao->study_participant_id,
        'study_status' => $dao->study_status,
        'eligible_status' => implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id)),
      ];
      // warning list for non-eligible volunteers
      $cleanEligibleStatus = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, "", $dao->eligible_status_id);
      if ($cleanEligibleStatus != Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue()) {
        $this->_countWarnings++;
        $this->_warnings[$dao->contact_id] = $volunteer;
      }
      $this->_countSelected++;
      $this->_selected[$dao->contact_id] = $volunteer;
    }
  }

  /**
   * Overridden parent method to prepare the form
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }

    $studyStatuses = CRM_Nihrbackbone_Utils::getOptionValueList('nbr_study_participation_status');
    $this->add('select', 'nbr_study_status_id', E::ts('Change status on study to'), $studyStatuses,
      TRUE, ['class' => 'crm-select2']);
    $this->assign('status_txt', E::ts('New status for selected volunteers:'));
    $this->assign('selected_txt', E::ts('Volunteers for which the status will be changed:'));
    $this->assign('warning_txt', E::ts('The volunteers below are not eligible and will NOT be processed if the new status is invitation pending, invited or accepted!'));
    $this->getSelectedData();
    $this->assign('count_selected_txt', E::ts('Number of volunteers whose status will be changed: ') . $this->_countSelected);
    $this->assign('count_warning_txt', E::ts('Number of volunteers that might not be processed: ') . $this->_countWarnings);
    $this->assign('selected', $this->_selected);
    $this->assign('warnings', $this->_warnings);
    $this->addDefaultButtons(ts('Change Study Status'));
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    if (isset($this->_submitValues['nbr_study_status_id'])) {
      $caseIds = $this->getRelevantCaseIds($this->_submitValues['nbr_study_status_id']);
      $newStatusLabel = CRM_Nihrbackbone_Utils::getOptionValueLabel($this->_submitValues['nbr_study_status_id'], CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId());
      foreach ($caseIds as $caseId => $caseData) {
        $this->_countProcessed++;
        CRM_Nihrbackbone_NbrVolunteerCase::updateStudyStatus($caseId, $caseData['contact_id'], $this->_submitValues['nbr_study_status_id']);
        $currentStatusLabel = CRM_Nihrbackbone_Utils::getOptionValueLabel($caseData['study_status_id'], CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId());
        $activityData = [
          'subject' => "Changed from status " . $currentStatusLabel . " to status " . $newStatusLabel,
          'status_id' => "Completed",
        ];
        CRM_Nihrbackbone_NbrVolunteerCase::addCaseActivity($caseId, $caseData['contact_id'], CRM_Nihrbackbone_BackboneConfig::singleton()->getChangedStudyStatusActivityTypeId(), $activityData);
        // if status is invited, add invite activity too
        if ($this->_submitValues['nbr_study_status_id'] == 'study_participation_status_invited') {
          CRM_Nihrbackbone_NbrInvitation::addInviteActivity($caseId, $caseData['contact_id'], $this->_studyId, 'Change Case Status Action');
        }
      }

      CRM_Core_Session::setStatus(E::ts('Updated status of ' . $this->_countProcessed . ' selected volunteers on study ') . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId)
        . E::ts(' to ') . $newStatusLabel, E::ts('Successfully changed status on study'), 'success');
    }
  }

  /**
   * Method to find the relevant case ids
   *
   * @return array
   */
  private function getRelevantCaseIds($newStatus) {
    $caseIds = [];
    $query = NULL;
    $queryParams = [];
    $i = 2;
    CRM_Nbrprojectvolunteerlist_Utils::getRelevantCaseIdsQuery((int) $this->_studyId, $query, $queryParams);
    // if new status in invited, invitation pending or accepted, only process if volunteer is eligible
    $contactIds = $this->selectValidVolunteers($newStatus);
    if (!empty($contactIds)) {
      $query .= " AND ccc.contact_id IN (";
      $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($contactIds, $i, $queryParams);
      $query .= implode("," , $elements) . ")";
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($dao->fetch()) {
        $caseIds[$dao->case_id] = [
          'study_status_id' => $dao->study_status_id,
          'contact_id' => $dao->contact_id,
        ];
      }
    }
    return $caseIds;
  }

  /**
   * Method to select the volunteers that are valid for processing
   *
   * @param $newStatus
   * @return array
   */
  private function selectValidVolunteers($newStatus) {
    $contactIds = [];
    // first found out if new status is an invited one
    $invited = explode(',', Civi::settings()->get('nbr_invited_study_status'));
    // if so, remove warnings from selected
    if (in_array($newStatus, $invited)) {
      foreach ($this->_warnings as $removeId => $warning) {
        unset($this->_selected[$removeId]);
      }
    }
    foreach ($this->_selected as $contactId => $selected) {
      $contactIds[] = $contactId;
    }
    return $contactIds;
  }

}


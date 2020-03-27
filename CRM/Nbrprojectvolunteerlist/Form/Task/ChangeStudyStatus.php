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
  private $_selected = [];
  private $_studyId = NULL;

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
      ];
      $eligibleStatus = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id));
      if (!empty($eligibleStatus)) {
        $volunteer['eligible_status'] = $eligibleStatus;
      }
      else {
        $volunteer['eligible_status'] = "Eligible";
      }
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
    $this->add('select', 'nbr_study_status_id', E::ts('Change status on study to'), CRM_Nihrbackbone_Utils::getOptionValueList('nbr_study_participation_status'),
      TRUE, ['class' => 'crm-select2']);
    $this->assign('status_txt', E::ts('New status for selected volunteers:'));
    $this->assign('selected_txt', E::ts('Volunteers for which the status will be changed:'));
    $this->assign('count_selected_txt', E::ts('Number of volunteers whose status will be changed: ') . $this->_countSelected);
    $this->getSelectedData();
    $this->assign('selected', $this->_selected);
    $this->addDefaultButtons(ts('Change Study Status'));
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    if (isset($this->_submitValues['nbr_study_status_id'])) {
      $caseIds = $this->getRelevantCaseIds();
      $newStatusLabel = CRM_Nihrbackbone_Utils::getOptionValueLabel($this->_submitValues['nbr_study_status_id'], CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId());
      foreach ($caseIds as $caseId => $caseData) {
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
      CRM_Core_Session::setStatus(E::ts('Updated status of selected volunteers on study ') . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId)
        . E::ts(' to ') . $newStatusLabel, E::ts('Successfully changed status on study'), 'success');
    }
  }

  /**
   * Method to find the relevant case ids
   *
   * @return array
   */
  private function getRelevantCaseIds() {
    $caseIds = [];
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $query = "SELECT ccc.case_id, cvnpd. " . $studyStatusColumn . " AS study_status_id, ccc.contact_id
        FROM " . $participationTable. " AS cvnpd
            LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
            LEFT JOIN civicrm_case AS cc ON ccc.case_id = cc.id
        WHERE cvnpd." . $studyColumn . " = %1 AND cc.is_deleted = %2 AND ccc.contact_id IN (";
    $queryParams = [
      1 => [$this->_studyId, "Integer"],
      2 => [0, "Integer"]
      ];
    $i = 2;
    $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($this->_contactIds, $i, $queryParams);
    $query .= implode("," , $elements) . ")";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $caseIds[$dao->case_id] = [
      'study_status_id' => $dao->study_status_id,
      'contact_id' => $dao->contact_id,
        ];
    }
    return $caseIds;
  }

}


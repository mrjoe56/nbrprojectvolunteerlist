<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the change status on project for volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 6 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_ChangeProjectStatus extends CRM_Contact_Form_Task {

  private $_countSelected = NULL;
  private $_selected = [];
  private $_projectId = NULL;

  /**
   * Method to get the data for the selected contact IDs
   *
   */
  private function getSelectedData() {
    $this->_selected = [];
    $bioResourceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_bioresource_id', 'column_name');
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $aliasTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomGroup('table_name');
    $ovpsOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId();
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnva." . $bioResourceColumn . " AS bioresource_id, 
        cvnpd." . $studyParticipantColumn . " AS study_participant_id, cvnpd." . $eligiblesColumn . " AS eligible_status_id,
        ovps.label AS project_status
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_option_value AS ovps ON cvnpd." . $statusColumn. " = ovps.value AND ovps.option_group_id = " . $ovpsOptionGroupId . "
        LEFT JOIN " . $aliasTable. " AS cvnva ON vol.id = cvnva.entity_id
        WHERE cvnpd." . $projectColumn. " = %1 AND cas.is_deleted = %2 AND vol.id IN (";
    $queryParams = [
      1 => [(int)$this->_projectId, "Integer"],
      2 => [0, "Integer"],
    ];
    $i = 2;
    $elements = [];
    foreach ($this->_contactIds as $contactId) {
      $i++;
      $queryParams[$i] = [(int) $contactId, 'Integer'];
      $elements[] = "%" . $i;
    }
    $query .= implode("," , $elements) . ")";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $volunteer = [
        'display_name' => $dao->display_name,
        'bioresource_id' => $dao->bioresource_id,
        'study_participant_id' => $dao->study_participant_id,
        'eligible_status' => implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id)),
        'project_status' => $dao->project_status,
      ];
      $this->_selected[$dao->contact_id] = $volunteer;
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['project_id'])) {
      $this->_projectId = self::$_searchFormValues['project_id'];
    }
    $this->add('select', 'nbr_project_status_id', E::ts('Change status on project to'), $this->getProjectStatusList(),
      TRUE, ['class' => 'crm-select2']);
    $this->assign('status_txt', E::ts('New status for selected volunteers:'));
    $this->assign('selected_txt', E::ts('Volunteers for which the status will be changed:'));
    $this->assign('count_selected_txt', E::ts('Number of volunteers whose status will be changed: ') . $this->_countSelected);
    $this->getSelectedData();
    $this->assign('selected', $this->_selected);
    $this->addDefaultButtons(ts('Change Project Status'));
  }

  /**
   * Method to get all potential project statuses
   *
   * @return array
   */
  private function getProjectStatusList() {
    $status = [];
    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'return' => ["label", "value"],
        'option_group_id' => "nbr_project_participation_status",
        'options' => ['limit' => 0],
        'is_active' => 1,
        ]);
      foreach ($result['values'] as $optionValueId => $optionValue) {
        $status[$optionValue['value']] = $optionValue['label'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $status;
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    if (isset($this->_submitValues['nbr_project_status_id'])) {
      $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_participation_status', 'column_name');
      $updateParams = [];
      $update = "";
      $caseIds = $this->getRelevantCaseIds();
      $elements = CRM_Nbrprojectvolunteerlist_Utils::setUpdateParams($caseIds, $statusColumn, $this->_submitValues['nbr_project_status_id'], $updateParams, $update);
      if (!empty($elements)) {
        $update .= implode(',', $elements) . ")";
        CRM_Core_DAO::executeQuery($update, $updateParams);
        $newStatusLabel = CRM_Nihrbackbone_Utils::getOptionValueLabel($this->_submitValues['nbr_project_status_id'], CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId());
        CRM_Nbrprojectvolunteerlist_CaseActivity::addStatusChangeActivities('project', $newStatusLabel, $caseIds);
        CRM_Core_Session::setStatus(E::ts('Updated status of selected volunteers on project ') . $this->_projectId
          . E::ts(' to ' . CRM_Nihrbackbone_Utils::getOptionValueLabel($this->_submitValues['nbr_project_status_id'],
              CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId())), E::ts('Successfully changed status on project'), 'success');
      }
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
    $projectStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_participation_status', 'column_name');
    $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $query = "SELECT ccc.case_id, cvnpd. " . $projectStatusColumn . " AS project_status_id
        FROM " . $participationTable. " AS cvnpd
            LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        WHERE cvnpd." . $projectColumn . " = %1 AND ccc.contact_id IN (";
    $queryParams = [1 => [$this->_projectId, "Integer"]];
    $i = 1;
    $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($this->_contactIds, $i, $queryParams);
    $query .= implode("," , $elements) . ")";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $caseIds[$dao->case_id] = $dao->project_status_id;
    }
    return $caseIds;
  }

}


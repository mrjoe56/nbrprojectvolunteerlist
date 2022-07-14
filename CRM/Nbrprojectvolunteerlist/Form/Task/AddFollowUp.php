<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process adding a follow up activity to volunteer(s)
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 14 Jul 2022
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_AddFollowUp extends CRM_Contact_Form_Task {

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
        'eligible_status' => implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id)),
      ];
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
    $this->addEntityRef('nbr_assignee_id', E::ts('Assigned to'), ['api' => ['params' => ['group' => 'nbr_bioresourcers'],],]);
    $this->add('text', 'nbr_subject', E::ts("Subject"), [], TRUE);
    $this->add('datepicker', 'nbr_activity_datetime', E::ts('Follow Up Date'), [],FALSE, ['time' => TRUE]);
    $this->add('wysiwyg', 'nbr_details', E::ts('Details'), ['rows' => 4, 'cols' => 100]);
    $this->addEntityRef('nbr_status_id', E::ts('Activity Status'), [
      'entity' => 'option_value',
      'api' => [
        'params' => ['option_group_id' => 'activity_status'],
      ],
      'select' => ['minimumInputLength' => 0],
    ]);
    $this->addEntityRef('nbr_priority_id', E::ts('Priority'), [
      'entity' => 'option_value',
      'api' => [
        'params' => ['option_group_id' => 'priority'],
      ],
      'select' => ['minimumInputLength' => 0],
    ]);
    $this->assign('selected_txt', E::ts('The following volunteers will get the follow up activity:'));
    $this->getSelectedData();
    $this->assign('count_selected_txt', E::ts('Number of volunteers that will get the follow up activity: ') . $this->_countSelected);
    $this->assign('selected', $this->_selected);
    $this->addDefaultButtons(E::ts('Add Follow Up'));
  }

  /**
   * Method to set defaults for add, edit and view mode
   *
   * @return array|NULL|void
   */
  public function setDefaultValues() {
    $activityDateTime = new DateTime('now');
    return [
      'nbr_activity_datetime' => $activityDateTime->format("YmdHis"),
      'nbr_status_id' => Civi::service('nbrBackbone')->getCompletedActivityStatusId(),
      'nbr_priority_id' => Civi::service('nbrBackbone')->getNormalPriorityId()
    ];
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    $caseIds = $this->getRelevantCaseIds();
    foreach ($caseIds as $caseId => $caseData) {
      CRM_Nihrbackbone_NbrVolunteerCase::addCaseActivity($caseId, $caseData['contact_id'], Civi::service('nbrBackbone')->getFollowUpActivityTypeId(), $this->setActivityData());
    }
    CRM_Core_Session::setStatus(E::ts('Added follow up activities to  selected volunteers on study ')
      . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId), E::ts('Added follow up activities'), 'success');
  }

  /**
   * Method to set the activity data for the follow up
   *
   * @return array
   * @throws Exception
   */
  private function setActivityData() {
    $activityDateTime = new DateTime('now');
    $activityData = ['subject' => $this->_submitValues['nbr_subject']];
    foreach ($this->_submitValues as $key => $value) {
      switch ($key) {
        case "nbr_assignee_id":
          $activityData['assignee_contact_id'] = $value;
          break;
        case "nbr_status_id":
          $activityData['status_id'] = $value;
          break;
        case "nbr_priority_id":
          $activityData['priority'] = $value;
          break;
        case "nbr_details":
          $activityData['details'] = trim($value);
          break;
        case "nbr_activity_datetime":
          $activityDateTime = new DateTime($value);
          break;
      }
      $activityData['activity_date_time'] = $activityDateTime->format("YmdHis");
    }
    return $activityData;
  }

  /**
   * Method to find the relevant case ids
   *
   * @return array
   */
  private function getRelevantCaseIds(): array {
    $caseIds = [];
    $query = "";
    $queryParams = [];
    $i = 2;
    CRM_Nbrprojectvolunteerlist_Utils::getRelevantCaseIdsQuery((int) $this->_studyId, $query, $queryParams);
    if (!empty($this->_selected)) {
      $query .= " AND ccc.contact_id IN (";
      $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($this->_contactIds, $i, $queryParams);
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

}


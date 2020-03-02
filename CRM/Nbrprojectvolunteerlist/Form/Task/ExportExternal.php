<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the change status on study for volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 6 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_ExportExternal extends CRM_Contact_Form_Task {

  private $_countSelected = NULL;
  private $_countInvalid = NULL;
  private $_selected = [];
  private $_invalids = [];
  private $_studyId = NULL;

  /**
   * Method to get the export data for the selected contact IDs
   *
   */
  private function getExportData() {
    $this->_invited = [];
    $this->_invalids = [];
    $this->_countInvited = 0;
    $this->_countInvalid = 0;
    $studyPartIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $recallColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $inviteColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $statusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    $genderOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId();
    $query = "SELECT a." . $studyPartIdColumn . " AS study_participant_id , a." . $recallColumn
      . " AS recall_group, a." . $statusColumn . " AS study_status_id, a. " . $inviteColumn . " AS date_invited,
      g.label AS study_status, c.first_name, c.last_name, h.label AS gender, d.email, e.street_address,
      TIMESTAMPDIFF(YEAR, c.birth_date , CURDATE()) AS age, e.city, e.postal_code, f.name AS county,
      b.contact_id, c.birth_date
      FROM " . $participantTable . " AS a
      LEFT JOIN civicrm_case_contact AS b ON a.entity_id = b.id
      LEFT JOIN civicrm_contact AS c ON b.contact_id = c.id
      LEFT JOIN civicrm_email AS d ON b.contact_id = d.contact_id AND d.is_primary = %1
      LEFT JOIN civicrm_address AS e ON b.contact_id = e.contact_id AND e.is_primary = %1
      LEFT JOIN civicrm_state_province AS f ON e.state_province_id = f.id
      LEFT JOIN civicrm_option_value AS g ON a.nvpd_study_participation_status = g.value AND g.option_group_id = %2
      LEFT JOIN civicrm_option_value AS h ON c.gender_id = h.value AND h.option_group_id = %3
      WHERE a." . $studyColumn . " = %4 AND c.id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [(int) $statusOptionGroupId, "Integer"],
      3 => [(int) $genderOptionGroupId, "Integer"],
      4 => [(int)$this->_studyId, "Integer"],
    ];
    $i = 4;
    CRM_Nbrprojectvolunteerlist_Utils::addContactIdsToQuery($i, $this->_contactIds, $query, $queryParams);
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $volunteer = [
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'study_participant_id' => $dao->study_participant_id,
        'recall_group' => $dao->recall_group,
        'age' => $dao->age,
        'gender' => $dao->gender,
        'birth_date' => date('d-m-Y', strtotime($dao->birth_date)),
        'email' => $dao->email,
        'street_address' => $dao->street_address,
        'city' => $dao->city,
        'county' => $dao->county,
        'postal_code' => $dao->postal_code,
        'date_invited' => date('d-m-Y', strtotime($dao->date_invited)),
      ];
      // add home and mobile phones
      $this->getPhones($dao->contact_id, $volunteer);
      // add if accepted else invalid
      if ($dao->study_status_id == "study_participation_status_accepted") {
        $this->_countSelected++;
        $this->_selected[$dao->contact_id] = $volunteer;
      }
      else {
        $this->_countInvalid++;
        $this->_invalids[$dao->contact_id] = $volunteer;
      }
    }
  }

  /**
   * Method to get the home and mobile phone for the volunteer (if any) :
   * get the primary for the contact, if it is not a mobile add the mobile
   *
   * @param $volunteer
   */
  private function getPhones($contactId, &$volunteer) {
    $mobilePhoneTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getMobilePhoneTypeId();
    $volunteer['phone'] = "";
    $volunteer['mobile'] = "";
    try {
      $primary = civicrm_api3('Phone', 'getsingle', [
        'contact_id' => $contactId,
        'is_primary' => 1,
      ]);
      if ($primary) {
        $volunteer['phone'] = $primary['phone'];
      }
      if ($primary['phone_type_id'] != $mobilePhoneTypeId) {
        $mobile = civicrm_api3('Phone', 'getsingle', [
          'sequential' => 1,
          'contact_id' => $contactId,
          'is_primary' => 0,
          'phone_type_id' => "Mobile",
          'options' => ['sort' => "id DESC", 'limit' => 1],
        ]);
        if ($mobile['phone']) {
          $volunteer['mobile'] = $mobile['phone'];
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->assign('selected_txt', E::ts('Accepted volunteers selected for export:') . $this->_countSelected);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be exported because they do NOT have status accepted:') . $this->_countInvalid);
    $this->getExportData();
    $this->assign('count_selected_txt', E::ts('Number of volunteers that will be exported: ') . $this->_countSelected);
    $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be exported: ') . $this->_countInvalid);
    $this->assign('selected', $this->_selected);
    $this->assign('invalids', $this->_invalids);
    $this->addDefaultButtons(ts('Export to CSV'));
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a study and selected volunteers
    if (isset($this->_studyId) && !empty($this->_selected)) {
      $fileName = "export_external_" . date('Y-m-d') . ".csv";
      $headers = ['First Name', 'Last Name', 'Study ID', 'Recall Group', 'Age', 'Gender', 'Date of Birth', 'Email', 'Street Address', 'City', 'County', 'Postcode', 'Date Invited', 'Phone', 'Mobile'];
      $rows = [];
      foreach ($this->_selected as $selectedId => $selectedData) {
        unset($selectedData['contact_id']);
        $rows[] = $selectedData;
      }
      CRM_Core_Report_Excel::writeCSVFile($fileName, $headers, $rows);
      CRM_Utils_System::civiExit();
    }
  }

}


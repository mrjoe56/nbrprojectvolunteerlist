<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the export on study for volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 8 Mar 2021
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_ExportSelect extends CRM_Contact_Form_Task {

  private $_studyId = NULL;

  /**
   * Method to build the basic query
   * @return string
   */
  private function buildQuery() {
    $studyPartIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $recallColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $inviteColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
    $distanceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_distance_volunteer_to_study_centre', 'column_name');
    $bioResourceIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField("nva_bioresource_id", "column_name");
    $participantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField("nva_participant_id", "column_name");
    $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $volunteerIdsTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup("table_name");
    $generalTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
    return "SELECT a." . $studyPartIdColumn . " AS study_participant_id , a." . $recallColumn
      . " AS recall, a." . $inviteColumn . " AS date_invited, a. " . $distanceColumn
      . " AS distance, h.label AS status, CONCAT_WS(' ', d.first_name, d.last_name) AS name,
      i.label AS gender, e.email, CONCAT_WS(', ', f.street_address, f.supplemental_address_1, f.supplemental_address_2,
      f.supplemental_address_3, f.city, f.postal_code) AS address, f.city, k.phone,
      m.label AS ethnicity, TIMESTAMPDIFF(YEAR, d.birth_date , CURDATE()) AS age, g.name AS county,
      j." . $bioResourceIdColumn . " AS bioresource_id, j." . $participantIdColumn . " AS participant_id,
      b.contact_id, b.case_id
      FROM " . $participantTable . " AS a
      LEFT JOIN civicrm_case_contact AS b ON a.entity_id = b.case_id
      JOIN civicrm_case AS c ON b.case_id = c.id
      LEFT JOIN civicrm_contact AS d ON b.contact_id = d.id
      LEFT JOIN civicrm_email AS e ON b.contact_id = e.contact_id AND e.is_primary = %1
      LEFT JOIN civicrm_address AS f ON b.contact_id = f.contact_id AND f.is_primary = %1
      LEFT JOIN civicrm_state_province AS g ON f.state_province_id = g.id
      LEFT JOIN civicrm_option_value AS h ON a." . $statusColumn . " = h.value AND h.option_group_id = %2
      LEFT JOIN civicrm_option_value AS i ON d.gender_id = i.value AND i.option_group_id = %3
      LEFT JOIN " . $volunteerIdsTable . " AS j ON d.id = j.entity_id
      LEFT JOIN civicrm_phone AS k ON d.id = k.contact_id AND k.is_primary = %1
      LEFT JOIN " . $generalTable . " AS l ON d.id = l.entity_id
      LEFT JOIN civicrm_option_value AS m ON l." .$ethnicityColumn . " = m.value AND m.option_group_id = %4
      WHERE a." . $studyColumn . " = %5 AND c.is_deleted = %6 AND d.id IN (";
  }

  /**
   * Method to build medication query
   * @return string
   */
  private function buildMedicationQuery() {
    $medicationTable = Civi::service('nbrBackbone')->getMedicationTableName();
    $medicationNameColumn = Civi::service('nbrBackbone')->getMedicationNameColumnName();
    $drugFamilyColumn = Civi::service('nbrBackbone')->getDrugFamilyColumnName();
    return "SELECT b.label AS medication_name, c.label AS drug_family
        FROM " . $medicationTable . " AS a
        LEFT JOIN civicrm_option_value AS b ON a." . $medicationNameColumn . " = b.value AND b.option_group_id = %1
        LEFT JOIN civicrm_option_value AS c ON a." . $drugFamilyColumn . " = c.value AND c.option_group_id = %2
        WHERE entity_id = %3";
  }

  /**
   * Method to build medication query parameters
   *
   * @param $contactId
   * @return array[]
   */
  private function buildMedicationQueryParams($contactId) {
    $medicationNameOptionGroupId = Civi::service('nbrBackbone')->getMedicationOptionGroupId();
    $drugFamilyOptionGroupId = Civi::service('nbrBackbone')->getDrugFamilyOptionGroupId();
    return [
      1 => [$medicationNameOptionGroupId, "Integer"],
      2 => [$drugFamilyOptionGroupId, "Integer"],
      3 => [(int) $contactId, "Integer"],
    ];
  }

  /**
   * Method to add the medication data to the volunteer row
   *
   * @param $volunteer
   */
  private function addMedicationData(&$volunteer) {
    $volunteer['medication_name'] = "";
    $volunteer['drug_family'] = "";
    $query = $this->buildMedicationQuery();
    $queryParams = $this->buildMedicationQueryParams($volunteer['contact_id']);
    $medications = [];
    $families = [];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      if (!empty($dao->medication_name)) {
        $medications[] = $dao->medication_name;
      }
      else {
        $medications[] = "";
      }
      if (!empty($dao->drug_family)) {
        $families[] = $dao->drug_family;
      }
      else {
        $families[] = "";
      }
    }
    if (!empty($medications)) {
      $volunteer['medication_name'] = implode("-", $medications);
    }
    if (!empty($families)) {
      $volunteer['drug_family'] = implode("-", $families);
    }
  }

  /**
   * Method to build the disease query
   *
   * @return string
   */
  private function buildDiseaseQuery() {
    $diseaseTable = Civi::service('nbrBackbone')->getDiseaseTableName();
    $diseaseColumn = Civi::service('nbrBackbone')->getDiseaseColumnName();
    $familyMemberColumn = Civi::service('nbrBackbone')->getFamilyMemberColumnName();
    $diseaseNotesColumn = Civi::service('nbrBackbone')->getDiseaseNotesColumnName();
    return "SELECT b.label AS disease, c.label AS family_member, a. " . $diseaseNotesColumn
      . " AS condition_notes
      FROM " . $diseaseTable . " AS a
        LEFT JOIN civicrm_option_value AS b ON a." . $diseaseColumn . " = b.value AND b.option_group_id = %1
        LEFT JOIN civicrm_option_value AS c ON a." . $familyMemberColumn . " = c.value AND c.option_group_id = %2
        WHERE entity_id = %3";
  }

  /**
   * Method to build disease query parameters
   *
   * @param $contactId
   * @return array[]
   */
  private function buildDiseaseQueryParams($contactId) {
    $diseaseOptionGroupId = Civi::service('nbrBackbone')->getDiseaseOptionGroupId();
    $familyMemberOptionGroupId = Civi::service('nbrBackbone')->getFamilyMemberOptionGroupId();
    return [
      1 => [$diseaseOptionGroupId, "Integer"],
      2 => [$familyMemberOptionGroupId, "Integer"],
      3 => [(int) $contactId, "Integer"],
    ];
  }

  /**
   * Method to add the disease data for the volunteer
   * @param $volunteer
   */
  private function addDiseaseData(&$volunteer) {
    $volunteer['disease'] = "";
    $volunteer['family_member'] = "";
    $volunteer['condition_notes'] = "";
    $query = $this->buildDiseaseQuery();
    $queryParams = $this->buildDiseaseQueryParams($volunteer['contact_id']);
    $diseases = [];
    $families = [];
    $notes = [];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      if (!empty($dao->disease)) {
        $diseases[] = $dao->disease;
      }
      else {
        $diseases[] = "";
      }
      if (!empty($dao->family_member)) {
        $families[] = $dao->family_member;
      }
      else {
        $families[] = "";
      }
      if (!empty($dao->condition_notes)) {
        $notes[] = $dao->condition_notes;
      }
      else {
        $notes[] = "";
      }
    }
    if (!empty($diseases)) {
      $volunteer['disease'] = implode("-", $diseases);
    }
    if (!empty($families)) {
      $volunteer['family_member'] = implode("-", $families);
    }
    if (!empty($notes)) {
      $volunteer['condition_notes'] = implode("-", $notes);
    }
  }

  /**
   * Method to build the query parameters
   * @return array[]
   */
  private function buildQueryParams() {
    $statusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    $genderOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId();
    $ethnicityOptionGroupId = Civi::service('nbrBackbone')->getEthnicityOptionGroupId();
    return [
      1 => [1, "Integer"],
      2 => [(int) $statusOptionGroupId, "Integer"],
      3 => [(int) $genderOptionGroupId, "Integer"],
      4 => [(int) $ethnicityOptionGroupId, "Integer"],
      5 => [(int) $this->_studyId, "Integer"],
      6 => [0, "Integer"],
    ];
  }

  /**
   * Method to get the export data for the selected contact IDs
   *
   */
  private function getExportData() {
    $volunteers = [];
    $query = $this->buildQuery();
    $queryParams = $this->buildQueryParams();
    $i = 8;
    CRM_Nbrprojectvolunteerlist_Utils::addContactIdsToQuery($i, $this->_contactIds, $query, $queryParams);
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $volunteer = CRM_Nihrbackbone_Utils::moveDaoToArray($dao);
      $this->addMedicationData($volunteer);
      $this->addDiseaseData($volunteer);
      $volunteers[] = $volunteer;
    }
    return $volunteers;
  }

  /**
   * Method to get the eligibilities for the case
   *
   * @param $caseId
   * @return string
   */
  private function getEligibility($caseId) {
    $result = [];
    $eligibilities = CRM_Nihrbackbone_NbrVolunteerCase::getCurrentEligibleStatus($caseId);
    foreach ($eligibilities as $eligibility) {
      $description = CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($eligibility);
      if (is_array($description)) {
        $result[] = $description[0];
      }
      else {
        $result[] = $description;
      }
    }
    return implode(", ", $result);
  }

  /**
   * Method to get export headers
   *
   * @return array
   */
  private function getHeaders() {
    $headers = [];
    if (isset($this->_submitValues['nbr_export_fields'])) {
      $exportFields = $this->getExportFieldList();
      foreach ($this->_submitValues['nbr_export_fields'] as $exportField) {
        $headers[] = $exportFields[$exportField];
      }
    }
    return $headers;
  }

  /**
   * Method to get the relavant data
   *
   * @return array
   */
  private function getRows() {
    $rows = [];
    if (isset($this->_submitValues['nbr_export_fields'])) {
      $volunteers = $this->getExportData();
      foreach ($volunteers as $volunteer) {
        $row = [];
        foreach ($this->_submitValues['nbr_export_fields'] as $exportField) {
          switch ($exportField) {
            case "location":
              $row[] = $volunteer['city'];
              break;
            case "eligibility":
              $row[] = $this->getEligibility($volunteer['case_id']);
              break;
            case "researcher_date":
              $exportDate = CRM_Nihrbackbone_NbrVolunteerCase::getLatestExportDate($row['case_id']);
              if ($exportDate) {
                $row[] = $this->fixDate($exportDate);
              }
              else {
                $row[] = "";
              }
              break;
            case "invite_date":
              $row[] = $this->fixDate($volunteer[$exportField]);
              break;
            case "visit_date":
              $latestVisitDate = CRM_Nihrbackbone_NbrVolunteerCase::getNearestVisit($row['case_id']);
              if ($latestVisitDate) {
                $row[] = $this->fixDate($latestVisitDate);
              }
              else {
                $row[] = "";
              }
              break;
            case "tags":
              $vol = new CRM_Nbrprojectvolunteerlist_NbrVolunteer();
              $row[] = $vol->getContactTags($volunteer['contact_id']);
              break;

            default:
              if (isset($volunteer[$exportField]) && !empty($volunteer[$exportField])) {
                $row[] = $volunteer[$exportField];
              }
              else {
                $row[] = "";
              }
              break;
          }
        }
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * Method to process the dates nicely
   *
   * @param $date
   * @return string
   */
  private function fixDate($date) {
    try {
      $fixDate = new DateTime($date);
      return $fixDate->format('d-m-Y');
    }
    catch (Exception $ex) {
      return "";
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->add('select', 'nbr_export_fields', E::ts('Fields to export'), $this->getExportFieldList(), TRUE, [
      'class' => 'crm-select2',
      'multiple' => TRUE,
    ]);
    $this->addDefaultButtons(ts('Export to CSV'));
  }

  /**
   * Method to get the list of fields available for export
   *
   * @return string[]
   */
  private function getExportFieldList() {
    return [
      "name" => "Name",
      "study_participant_id" => "Study Participation ID",
      "gender" => "Gender",
      "age" => "Age",
      "ethnicity" => "Ethnicity",
      "location" => "Location",
      "address" => "Address",
      "email" => "Email",
      "phone" => "Phone",
      "tags" => "Tag(s)",
      "medication_name" => "Medication Name",
      "drug_family" => "Drug Family",
      "disease" => "Disease",
      "family_member" => "Family Member",
      "condition_notes" => "Condition Notes",
      "distance" => "Distance",
      "eligibility" => "Eligibility",
      "recall" => "Recall Group",
      "status" => "Study Status",
      "invite_date" => "Invite Date",
      "researcher_date" => "Sent to Researcher Date",
      "visit_date" => "Latest Visit Date",
      "participant_id" => "Participant ID",
      "bioresource_id" => "BioResource ID",
    ];
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a study
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    if (isset($this->_studyId)) {
      $fileName = "export_" . date('Y-m-d') . ".csv";
      CRM_Core_Report_Excel::writeCSVFile($fileName, $this->getHeaders(), $this->getRows());
      CRM_Utils_System::civiExit();
    }
  }

}


<?php
use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('NIHR BioResource Volunteer List'));
    $form->add('select','project_id', E::ts('Project Code'), $this->getProjectList(),[
      'class' => 'crm-select2',
      'multiple' => TRUE,
    ] ,TRUE
    );
    // Optionally define default search values
    $form->setDefaults([
      'project_id' => NULL,
    ]);

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', ['project_id']);
  }

  /**
   * Method to build the list of projects
   * @return array
   */
  private function getProjectList() {
    try {
      $apiValues = civicrm_api3('Campaign', 'get', [
        'return' => ['title'],
        'campaign_type_id' => "nihr_project",
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiValues['values'] as $projectId => $project) {
        if (isset($project['title'])) {
          $result[$projectId] = $project['title'];
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = [
      E::ts('Name') => 'sort_name',
      E::ts('BioResource ID') => 'nva_bioresource_id',
      E::ts('Study/Part ID') => 'nvpd_study_participant_id',
      E::ts('Gender') => 'gender',
      E::ts('Age') => 'birth_date',
      E::ts('Ethnicity') => 'ethnicity',
      E::ts('Location') => 'volunteer_address',
      E::ts('Eligibility') => 'nvpd_eligible_status_id',
      E::ts('Project Status') => 'project_status',
      E::ts('Study Status') => 'study_status',
      E::ts('Invite Date') => 'nvpd_date_invited',
      E::ts('Distance') => 'nvpd_distance_volunteer_to_study_centre',
    ];
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    $bioResourceIDColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_bioresource_id', 'column_name');
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $studyParticipantIDColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $dateInvitedColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
    $distanceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_distance_volunteer_to_study_centre', 'column_name');
    return "
      DISTINCT(contact_a.id) AS contact_id, contact_a.sort_name, nva." . $bioResourceIDColumn . ", 
      contact_a.birth_date, genderov.label AS gender, ethnicov.label AS ethnicity, adr.city AS volunteer_address, 
      nvpd." . $eligibleColumn . ", nvpd.". $studyParticipantIDColumn . ", prostatus.label AS project_status, 
      stustatus.label AS study_status, nvpd." . $dateInvitedColumn . ", nvpd." . $distanceColumn;
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    $nvgoTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
    $nvpdTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $nvaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomGroup('table_name');
    $genderOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId();
    $ethnicityOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId();
    $projectStatusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId();
    $studyStatusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
    $projectStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_participation_status', 'column_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    return "
      FROM civicrm_contact AS contact_a
      JOIN civicrm_case_contact AS ccc ON contact_a.id = ccc.contact_id
      JOIN civicrm_case AS cas ON ccc.case_id = cas.id AND cas.is_deleted = 0
      LEFT JOIN " . $nvaTable . " AS nva ON contact_a.id = nva.entity_id
      LEFT JOIN " . $nvgoTable . " AS nvgo ON ccc.contact_id = nvgo.entity_id
      LEFT JOIN civicrm_address AS adr ON contact_a.id = adr.contact_id AND adr.is_primary = 1
      LEFT JOIN " . $nvpdTable . " AS nvpd ON cas.id = nvpd.entity_id
      LEFT JOIN civicrm_option_value AS genderov ON contact_a.gender_id = genderov.value AND genderov.option_group_id = " . $genderOptionGroupId ." 
      LEFT JOIN civicrm_option_value AS ethnicov ON nvgo." . $ethnicityColumn . " = ethnicov.value AND ethnicov.option_group_id = " . $ethnicityOptionGroupId . "
      LEFT JOIN civicrm_option_value AS prostatus ON nvpd." . $projectStatusColumn . " = prostatus.value AND prostatus.option_group_id = " . $projectStatusOptionGroupId . "
      LEFT JOIN civicrm_option_value AS stustatus ON nvpd." . $studyStatusColumn . " = stustatus.value AND stustatus.option_group_id = " . $studyStatusOptionGroupId;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = [1 => ["nihr_volunteer", "String"]];
    $where = "contact_a.contact_sub_type   = %1";
    if (isset($this->_formValues['project_id']) && !empty($this->_formValues['project_id'])) {
      $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
      $clause[] = "nvpd." . $projectIdColumn . " = %2";
      $params[2] = [$this->_formValues['project_id'], "Integer"];
    }
    if (!empty($clause)) {
      $where .= ' AND ' . implode(' AND ', $clause);
    }
    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    foreach ($row as $fieldName => &$field) {
      switch ($fieldName) {
        case 'birth_date':
          $birthDate = $row[$fieldName];
          $row[$fieldName] = "unknown";
          if (!empty($birthDate)) {
            if (!empty($row[$fieldName])) {
              $age = CRM_Utils_Date::calculateAge($birthDate);
              if(isset($age['years'])) {
                $row['birth_date'] = $age['years'];
              }
            }
          }
          break;

        case 'invite_date':
          if (!empty($row[$fieldName])) {
            $row[$fieldName] = date('d-m-Y', strtotime($row[$fieldName]));
          }
          break;

        case 'nvpd_eligible_status_id':
          if (empty($row[$fieldName])) {
            $row[$fieldName] = "Eligible";
          }
          else {
            $row[$fieldName] = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($row[$fieldName]));
          }
          break;
      }
    }
  }
}

<?php
use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  private $_force = NULL;
  private $_studyId = NULL;

  /**
   * CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList constructor.
   *
   * @param $formValues
   * @throws CRM_Core_Exception
   */
  function __construct(&$formValues) {
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean');
    if ($this->_force) {
      foreach (array_keys($this->getSearchFieldMetadata()) as $entity) {
        $formValues = array_merge($this->getEntityDefaults($entity), $formValues);
      }
    }
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('Manage Study Participation'));
    $form->add('select','study_id', E::ts('Study'), $this->getStudyList(), TRUE,
      ['class' => 'crm-select2', 'placeholder' => '- select study -']);
    $form->add('select','gender_id', E::ts('Gender is one of'), CRM_Nihrbackbone_Utils::getOptionValueList('gender'), FALSE,
      ['class' => 'crm-select2', 'placeholder' => ' - select gender(s) -', 'multiple' => TRUE]);
    $form->add('text', 'first_name', E::ts('First Name contains'), [], FALSE);
    $form->add('text', 'last_name', E::ts('Last Name contains'), [], FALSE);
    if ($this->_studyId) {
      $form->add('select', 'recall_group', E::ts('Recall Group'), CRM_Nihrbackbone_NbrStudy::getRecallGroupList($this->_studyId), FALSE,
        ['class' => 'crm-select2', 'placeholder' => '- select recall group -', 'multiple' => TRUE]);
    }
    else {
      $form->add('select', 'recall_group', E::ts('Recall Group'), CRM_Nihrbackbone_NbrStudy::getRecallGroupList(), FALSE,
        ['class' => 'crm-select2', 'placeholder' => '- select recall group -', 'multiple' => TRUE]);
    }
    $form->add('select', 'study_status_id', E::ts('Status'), CRM_Nihrbackbone_Utils::getOptionValueList(CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId()), FALSE,
    ['class' => 'crm-select2', 'placeholder' => '- select status -', 'multiple' => TRUE]);
    $form->add('select', 'age_from', E::ts('Age From'), CRM_Nihrbackbone_Utils::getAgeList(), FALSE,
    ['class' => 'crm-select2', 'placeholder' => '- select age from -']);
    $form->add('select', 'age_to', E::ts('Age To'), CRM_Nihrbackbone_Utils::getAgeList(), FALSE,
    ['class' => 'crm-select2', 'placeholder' => '- select age to -']);
    // Optionally define default search values
    $form->setDefaults([
      'study_id' => NULL,
    ]);
    $this->assignFilters($form);
  }

  /**
   * assign filters
   *
   * @param $form
   */
  private function assignFilters(&$form) {
    $form->assign('elements', [
      'study_id',
      'first_name',
      'last_name',
      'gender_id',
      'recall_group',
      'study_status_id',
      'age_from',
      'age_to']);
  }

  /**
   * Method to build the list of studies
   * @return array
   */
  private function getStudyList() {
    $result = [];
    $studyNumberFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_study_number', 'id');
    try {
      $apiValues = civicrm_api3('Campaign', 'get', [
        'return' => [$studyNumberFieldId, 'title'],
        'campaign_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId(),
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiValues['values'] as $studyId => $study) {
        if (isset($study[$studyNumberFieldId])) {
          $result[$studyId] = $study[$studyNumberFieldId] . " (" . $study['title'] . ")";
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
    $searchFilters = [
      'study_id' => 'Study ',
      'first_name' => 'First name contains ',
      'last_name' => 'Last name contains ',
      'gender_id' => 'Gender is one of ',
      'recall_group' => 'Recall Group is one of ',
      'study_status_id' => 'Status is one of ',
      'age_from' => 'Age from ',
      'age_to' => 'Age to ',
    ];
    $filters = [];
    foreach ($searchFilters as $searchFilter => $searchTxt) {
      if (isset($this->_formValues[$searchFilter]) && !empty($this->_formValues[$searchFilter])) {
        switch ($searchFilter) {
          case "study_id":
            $filters[] = $searchTxt . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_formValues[$searchFilter]);
            break;
          case "gender_id":
            $genderLabels = [];
            foreach ($this->_formValues[$searchFilter] as $gender) {
              $genderLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($gender, 'gender');
            }
            $filters[] = $searchTxt . implode(", ", $genderLabels);
            break;
          case "study_status_id":
            $statusLabels = [];
            foreach ($this->_formValues[$searchFilter] as $status) {
              $statusLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($status, CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId());
            }
            $filters[] = $searchTxt . implode(", ", $statusLabels);
            break;
          default:
            if (is_array($this->_formValues[$searchFilter])) {
              $textLabels = [];
              foreach ($this->_formValues[$searchFilter] as $value) {
                $textLabels[] = $value;
              }
              $filters[] = $searchTxt . implode(", ", $textLabels);
            }
            else {
              $filters[] = $searchTxt . $this->_formValues[$searchFilter];
            }
            break;
        }
      }
    }
    return ['summary' => "Filter(s) " . implode(" and " , $filters)];
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
      E::ts('Study/Part ID') => 'nvpd_study_participant_id',
      E::ts('Gndr') => 'gender',
      E::ts('Age') => 'birth_date',
      E::ts('Ethn.') => 'ethnicity',
      E::ts('Loc.') => 'volunteer_address',
      E::ts('Distance') => 'nvpd_distance_volunteer_to_study_centre',
      E::ts('Eligibility') => 'nvpd_eligible_status_id',
      E::ts('Recall Group') => 'nvpd_recall_group',
      E::ts('Status') => 'study_status',
      E::ts('Inv. Date') => 'nvpd_date_invited',
      E::ts('Researcher Date') => 'date_researcher',
      E::ts('Latest Visit Date') => 'latest_visit_date',
      E::ts('Case ID') => 'case_id'
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
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $studyParticipantIDColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $dateInvitedColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
    $distanceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_distance_volunteer_to_study_centre', 'column_name');
    $recallColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
    return "
      DISTINCT(contact_a.id) AS contact_id, cas.id AS case_id, contact_a.sort_name, contact_a.birth_date, genderov.label AS gender,
      ethnicov.label AS ethnicity, adr.city AS volunteer_address, nvpd." . $eligibleColumn . ", nvpd.". $studyParticipantIDColumn
      . ", nvpd." . $recallColumn . ", stustatus.label AS study_status, nvpd."
      . $dateInvitedColumn . ", nvpd." . $distanceColumn . ", '' AS date_researcher, '' AS latest_visit_date";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    $nvgoTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
    $nvpdTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $nviTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('table_name');
    $genderOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId();
    $ethnicityOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId();
    $studyStatusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
    $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    return "
      FROM civicrm_contact AS contact_a
      JOIN civicrm_case_contact AS ccc ON contact_a.id = ccc.contact_id
      JOIN civicrm_case AS cas ON ccc.case_id = cas.id AND cas.is_deleted = 0
      LEFT JOIN " . $nvgoTable . " AS nvgo ON ccc.contact_id = nvgo.entity_id
      LEFT JOIN civicrm_address AS adr ON contact_a.id = adr.contact_id AND adr.is_primary = 1
      LEFT JOIN " . $nvpdTable . " AS nvpd ON cas.id = nvpd.entity_id
      LEFT JOIN " . $nviTable . " AS nvi ON contact_a.id = nvi.entity_id
      LEFT JOIN civicrm_option_value AS genderov ON contact_a.gender_id = genderov.value AND genderov.option_group_id = " . $genderOptionGroupId ."
      LEFT JOIN civicrm_option_value AS ethnicov ON nvgo." . $ethnicityColumn . " = ethnicov.value AND ethnicov.option_group_id = " . $ethnicityOptionGroupId . "
      LEFT JOIN civicrm_option_value AS stustatus ON nvpd." . $studyStatusColumn . " = stustatus.value AND stustatus.option_group_id = " . $studyStatusOptionGroupId;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $clauses = [];
    $params = [1 => ["nihr_volunteer", "String"]];
    $index = 1;
    $where = "contact_a.contact_sub_type   = %1";
    $this->addEqualsClauses($index, $clauses, $params);
    $this->addLikeClauses($index, $clauses, $params);
    $this->addDateRangeClauses($index, $clauses, $params);
    if (!empty($clauses)) {
      $where .= ' AND ' . implode(' AND ', $clauses);
    }
    $this->addMultipleClauses($index, $where, $params);
    return $this->whereClause($where, $params);
  }

  /**
   * Method to add multiple clauses
   *
   * @param $index
   * @param $where
   * @param $params
   */
  private function addMultipleClauses(&$index, &$where, &$params) {
    $multipleFields = ['gender_id', 'recall_group', 'study_status_id'];
    foreach ($multipleFields as $multipleField) {
      $clauses = [];
      if (isset($this->_formValues[$multipleField]) && !empty($this->_formValues[$multipleField])) {
        switch ($multipleField) {
          case 'gender_id':
            foreach ($this->_formValues[$multipleField] as $multipleValue) {
              $index++;
              $clauses[] = "contact_a.gender_id = %" . $index;
              $params[$index] = [(int) $multipleValue, "Integer"];
            }
            if (!empty($clauses)) {
              $where .= " AND (" . implode(" OR ", $clauses) . ")";
            }
            break;

          case 'recall_group':
            $recallColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
            foreach ($this->_formValues[$multipleField] as $multipleValue) {
              $index++;
              $clauses[] = "nvpd." . $recallColumn . " = %" . $index;
              $params[$index] = [$multipleValue, "String"];
            }
            if (!empty($clauses)) {
              $where .= " AND (" . implode(" OR ", $clauses) . ")";
            }
            break;

          case 'study_status_id':
            $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
            foreach ($this->_formValues[$multipleField] as $multipleValue) {
              $index++;
              $clauses[] = "nvpd." . $statusColumn . " = %" . $index;
              $params[$index] = [$multipleValue, "String"];
            }
            if (!empty($clauses)) {
              $where .= " AND (" . implode(" OR ", $clauses) . ")";
            }
            break;
        }
      }
    }
  }

  /**
   * Method to add the equals clauses
   * @param $index
   * @param $clauses
   * @param $params
   */
  private function addEqualsClauses(&$index, &$clauses, &$params) {
    $equalFields = ['study_id'];
    foreach ($equalFields as $equalField) {
      if (isset($this->_formValues[$equalField]) && !empty($this->_formValues[$equalField])) {
        $index++;
        switch ($equalField) {
          case 'study_id':
            $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
            $clauses[] = "nvpd." . $studyIdColumn . " = %" . $index;
            $params[$index] = [$this->_formValues['study_id'], "Integer"];
            break;
        }
      }
    }
  }

  /**
   * Method to add the date range clauses
   *
   * @param $index
   * @param $clauses
   * @param $params
   */
  private function addDateRangeClauses(&$index, &$clauses, &$params) {
    $rangeFields = ['age'];
    foreach ($rangeFields as $rangeField) {
      $fromField = $rangeField . "_from";
      $toField = $rangeField . "_to";
      switch ($rangeField) {
        case 'age':
          if (isset($this->_formValues[$fromField]) && !empty($this->_formValues[$fromField])) {
            $index++;
            $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) >= %" . $index;
            $params[$index] = [$this->_formValues[$fromField], "String"];
          }
          if (isset($this->_formValues[$toField]) && !empty($this->_formValues[$toField])) {
            $index++;
            $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) <= %" . $index;
            $params[$index] = [$this->_formValues[$toField], "String"];
          }
          break;
      }
    }
  }

  /**
   * Method to add like clauses
   *
   * @param int $index
   * @param array $clauses
   * @param array $params
   */
  private function addLikeClauses(&$index, &$clauses, &$params) {
    $likeFields = ['first_name', 'last_name'];
    foreach ($likeFields as $likeField) {
      if (isset($this->_formValues[$likeField]) && !empty($this->_formValues[$likeField])) {
        $index++;
        $params[$index] = ["%" . $this->_formValues[$likeField] . "%", "String"];
        $clauses[] = "contact_a." . $likeField . " LIKE %" . $index;
      }
    }
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Nbrprojectvolunteerlist/Form/Search/VolunteerList.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    foreach ($row as $fieldName => &$field) {
      // add case url
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

        case 'date_researcher':
          $exportDate = CRM_Nihrbackbone_NbrVolunteerCase::getLatestExportDate($row['case_id']);
          if ($exportDate) {
            $row['date_researcher'] = $exportDate;
          }
          break;

        case 'ethnicity':
          $parts = explode('(', $row['ethnicity']);
          $row['ethnicity'] = trim($parts[0]);
          break;

        case 'gender':
          $row['gender'] = substr($row['gender'],0,1);
          break;

        case 'latest_visit_date':
          $latestVisitDate = CRM_Nihrbackbone_NbrVolunteerCase::getLatestVisit($row['case_id']);
          if ($latestVisitDate) {
            $row['latest_visit_date'] = $latestVisitDate;
          }
          break;

        case 'nvpd_date_invited':
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
  /**
   * @return array
   */
  public function getSearchFieldMetadata() {
    $searchFieldMetadata['contact'] = [
      'sort_name' => [
        'title' => ts('Sort Name'),
        'type' => CRM_Utils_Type::T_STRING,
      ]
    ];
    return $searchFieldMetadata;
  }

  /**
   * Get the validation rule to apply to a function.
   *
   * Alphanumeric is designed to always be safe & for now we just return
   * that but in future we can use tighter rules for types like int, bool etc.
   *
   * @param string $entity
   * @param string $fieldName
   *
   * @return string
   */
  protected function getValidationTypeForField($entity, $fieldName) {
    switch ($this->getSearchFieldMetadata()[$entity][$fieldName]['type']) {
      case CRM_Utils_Type::T_BOOLEAN:
        return 'Boolean';

      case CRM_Utils_Type::T_INT:
        return 'CommaSeparatedIntegers';

      case CRM_Utils_Type::T_DATE:
      case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
        return 'Timestamp';

      default:
        return 'Alphanumeric';
    }
  }

  /**
   * Get the defaults for the entity for any fields described in metadata.
   *
   * @param string $entity
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getEntityDefaults($entity) {
    $defaults = [];
    foreach (CRM_Utils_Array::value($entity, $this->getSearchFieldMetadata(), []) as $fieldName => $fieldSpec) {
      if (empty($_POST[$fieldName])) {
        $value = CRM_Utils_Request::retrieveValue($fieldName, $this->getValidationTypeForField($entity, $fieldName), NULL, NULL, 'GET');
        if ($value !== NULL) {
          $defaults[$fieldName] = $value;
        }
        if ($fieldSpec['type'] === CRM_Utils_Type::T_DATE || ($fieldSpec['type'] === CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME)) {
          $low = CRM_Utils_Request::retrieveValue($fieldName . '_low', 'Timestamp', NULL, NULL, 'GET');
          $high = CRM_Utils_Request::retrieveValue($fieldName . '_high', 'Timestamp', NULL, NULL, 'GET');
          if ($low !== NULL || $high !== NULL) {
            $defaults[$fieldName . '_relative'] = 0;
            $defaults[$fieldName . '_low'] = $low ? date('Y-m-d H:i:s', strtotime($low)) : NULL;
            $defaults[$fieldName . '_high'] = $high ? date('Y-m-d H:i:s', strtotime($high)) : NULL;
          }
          else {
            $relative = CRM_Utils_Request::retrieveValue($fieldName . '_relative', 'String', NULL, NULL, 'GET');
            if (!empty($relative) && isset(CRM_Core_OptionGroup::values('relative_date_filters')[$relative])) {
              $defaults[$fieldName . '_relative'] = $relative;
            }
          }
        }
      }
    }
    // if the study id is in the request, start the search with this study
    if ($this->_force) {
      $this->_studyId = CRM_Utils_Request::retrieveValue('sid', 'Integer');
      if ($this->_studyId) {
        $defaults['study_id'] = $this->_studyId;
      }
    }
    return $defaults;
  }

}

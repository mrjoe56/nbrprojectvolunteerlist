<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface
{

    private $_force = NULL;
    private $_studyId = NULL;
    private $_eligibleParticipationStatus = [];

    /**
     * CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList constructor.
     *
     * @param $formValues
     * @throws CRM_Core_Exception
     */
    function __construct(&$formValues)
    {
        $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean');
        if ($this->_force) {
            $formValues = array_merge($this->getEntityDefaults('study'), $formValues);
        }
        $eligibleParticipationStatus = Civi::settings()->get('nbr_eligible_calc_study_status');
        $optionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
        if (!empty($eligibleParticipationStatus)) {
            if (!is_array($eligibleParticipationStatus)) {
                $parts = explode(',', $eligibleParticipationStatus);
                foreach ($parts as $part) {
                    $this->_eligibleParticipationStatus[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($part, $optionGroupId);
                }
            } else {
                foreach ($eligibleParticipationStatus as $part) {
                    $this->_eligibleParticipationStatus[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($part, $optionGroupId);
                }
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
    function buildForm(&$form)
    {
        CRM_Utils_System::setTitle(E::ts('Manage Study Participation'));
        $selectedIds = $this->getSelectedIds();
        $this->getTagList();
        $form->assign_by_ref('selectedIds', $selectedIds);
        $form->add('select', 'study_id', E::ts('Study'), $this->getStudyList(), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select study -']);
        $form->add('select', 'gender_id', E::ts('Gender is one of'), CRM_Nihrbackbone_Utils::getOptionValueList('gender'), FALSE,
            ['class' => 'crm-select2', 'placeholder' => ' - select gender(s) -', 'multiple' => TRUE]);
        $form->addRadio('inex_gender_id', "", ['incl', 'excl'], [], " ");
        $defaults['inex_gender_id'] = 0;
        $form->add('select', 'ethnicity_id', E::ts('Ethnicity is one of'), CRM_Nihrbackbone_Utils::getOptionValueList(CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId()), FALSE,
            ['class' => 'crm-select2', 'placeholder' => ' - select ethnicit(y)(iess) -', 'multiple' => TRUE]);
        $form->addRadio('inex_ethnicity_id', "", ['incl', 'excl'], [], " ");
        $defaults['inex_ethnicity_id'] = 0;
        $form->add('text', 'study_participant_id', E::ts('Study Participant ID contains'), [], FALSE);
        $form->add('text', 'first_name', E::ts('First Name contains'), [], FALSE);
        $form->add('text', 'last_name', E::ts('Last Name contains'), [], FALSE);
        $form->add('text', 'participant_id', E::ts('Participant ID contains'), [], FALSE);
        $form->add('text', 'bioresource_id', E::ts('BioResource ID contains'), [], FALSE);
        if ($this->_studyId) {
            $form->add('select', 'recall_group', E::ts('Recall Group'), CRM_Nihrbackbone_NbrStudy::getRecallGroupList($this->_studyId), FALSE,
                ['class' => 'crm-select2', 'placeholder' => '- select recall group -', 'multiple' => TRUE]);
        } else {
            $form->add('select', 'recall_group', E::ts('Recall Group'), CRM_Nihrbackbone_NbrStudy::getRecallGroupList(), FALSE,
                ['class' => 'crm-select2', 'placeholder' => '- select recall group -', 'multiple' => TRUE]);
        }
        $form->addRadio('inex_recall_group', "", ['incl', 'excl'], [], " ");
        $defaults['inex_recall_group'] = 0;
        $form->add('select', 'study_status_id', E::ts('Status'), CRM_Nihrbackbone_Utils::getOptionValueList(CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId()), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select status -', 'multiple' => TRUE]);
        $form->addRadio('inex_study_status_id', "", ['incl', 'excl'], [], " ");
        $defaults['inex_study_status_id'] = 0;
        $form->add('select', 'eligibility_status_id', E::ts('Eligibility'), CRM_Nihrbackbone_Utils::getOptionValueList(CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId()), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select eligibility -', 'multiple' => TRUE]);
        $form->addRadio('inex_eligibility_status_id', "", ['incl', 'excl'], [], " ", TRUE);
        $defaults['inex_eligibility_status_id'] = 0;
        $form->add('select', 'tags', E::ts('Tags'), $this->getTagList(), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select tag(s) -', 'multiple' => TRUE]);
        $form->addRadio('inex_tags', "", ['incl', 'excl'], [], " ");
        $defaults['inex_tags'] = 0;
        $form->add('datepicker', 'invite_date_from', E::ts('Invite date from'), [], FALSE, ['time' => FALSE]);
        $form->add('datepicker', 'invite_date_to', E::ts('Invite date to'), [], FALSE, ['time' => FALSE]);
        $form->addRadio('inex_invite_date', "", ['incl', 'excl'], [], " ");
        $defaults['inex_invite_date'] = 0;
        $form->add('select', 'age_from', E::ts('Age From'), CRM_Nihrbackbone_Utils::getAgeList(), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select age from -']);
        $form->add('select', 'age_to', E::ts('Age To'), CRM_Nihrbackbone_Utils::getAgeList(), FALSE,
            ['class' => 'crm-select2', 'placeholder' => '- select age to -']);
        $form->addRadio('inex_age', "", ['incl', 'excl'], [], " ");
        $form->addRadio('has_email', E::ts('Has Email?'), ["All", "Yes", "No"], ['value' => "0"], NULL, TRUE);
        $form->addRadio('has_mobile', E::ts('Has Mobile Phone?'), ["All", "Yes", "No"], ['value' => "0"], NULL, TRUE);

        $defaults['inex_age'] = 0;
        $defaults['has_email'] = 0;
        $defaults['has_mobile'] = 0;
        // Optionally define default                                                                                                                                                                                        search values
        $defaults['study_id'] = NULL;
        $form->setDefaults($defaults);
        $this->assignFilters($form);
    }

    /**
     * assign filters
     *
     * @param $form
     */
    private function assignFilters(&$form)
    {
        $form->assign('elements', [
            'study_id',
            'study_participant_id',
            'first_name',
            'last_name',
            'participant_id',
            'bioresource_id',
            'gender_id',
            'ethnicity_id',
            'recall_group',
            'tags',
            'study_status_id',
            'eligibility_status_id',
            'invite_date_from',
            'invite_date_to',
            'age_from',
            'age_to',
            'has_email',
            'has_mobile',
        ]);
    }

    /**
     * Method to build the list of tags
     * @return array
     * @throws API_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    private function getTagList()
    {
        $result = [];
        $tags = \Civi\Api4\Tag::get()
            ->addSelect('id', 'name')
            ->setCheckPermissions(FALSE)->execute();
        foreach ($tags as $tag) {
            $result[$tag['id']] = $tag['name'];
        }
        return $result;
    }

    /**
     * Method to build the list of studies
     * @return array
     */
    private function getStudyList()
    {
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
        } catch (CiviCRM_API3_Exception $ex) {
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
    function summary()
    {
        $searchFilters = [
            'study_id' => 'Study ',
            'study_participant_id' => 'Study Participant ID ',
            'first_name' => 'First name ',
            'last_name' => 'Last name ',
            'has_email' => 'Has Email',
            'has_mobile' => 'Has Mobile',
            'gender_id' => 'Gender is ',
            'ethnicity_id' => 'Ethnicity is ',
            'participant_id' => 'Participant ID ',
            'bioresource_id' => 'BioResource ID ',
            'recall_group' => 'Recall Group is ',
            'tags' => ' Tag(s) ',
            'study_status_id' => 'Status is ',
            'eligibility_status_id' => 'Eligibility is ',
            'invite_date_from' => 'Invite Date ',
            'invite_date_to' => 'Invite Date ',
            'age_from' => 'Age ',
            'age_to' => 'Age ',
        ];
        $filters = [];
        foreach ($searchFilters as $searchFilter => $searchTxt) {
            if (isset($this->_formValues[$searchFilter]) && !empty($this->_formValues[$searchFilter])) {
                switch ($searchFilter) {
                    case "study_id":
                        $filters[] = $searchTxt . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_formValues[$searchFilter]);
                        break;
                    case "tags":
                        $tagLabels = [];
                        $tagList = $this->getTagList();
                        foreach ($this->_formValues[$searchFilter] as $key => $tag) {
                            $tagLabels[] = $tagList[$tag];
                        }
                        if (isset($this->_formValues['inex_tags']) && $this->_formValues['inex_tags'] == 1) {
                            $filters[] = "does not have " . $searchTxt . implode(",", $tagLabels);
                        }
                        $filters[] = "has " . $searchTxt . implode(",", $tagLabels);
                        break;
                    case "gender_id":
                        $genderLabels = [];
                        foreach ($this->_formValues[$searchFilter] as $gender) {
                            $genderLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($gender, 'gender');
                        }
                        if (isset($this->_formValues['inex_gender_id']) && $this->_formValues['inex_gender_id'] == 1) {
                            $filters[] = $searchTxt . " not " . implode(", ", $genderLabels);
                        } else {
                            $filters[] = $searchTxt . implode(", ", $genderLabels);
                        }
                        break;
                    case "ethnicity_id":
                        $ethnicityLabels = [];
                        foreach ($this->_formValues[$searchFilter] as $ethnicity) {
                            $ethnicityLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($ethnicity, CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId());
                        }
                        if (isset($this->_formValues['inex_ethnicity_id']) && $this->_formValues['inex_ethnicity_id'] == 1) {
                            $filters[] = $searchTxt . " not " . implode(", ", $ethnicityLabels);
                        } else {
                            $filters[] = $searchTxt . implode(", ", $ethnicityLabels);
                        }
                        break;
                    case "study_status_id":
                        $statusLabels = [];
                        foreach ($this->_formValues[$searchFilter] as $status) {
                            $statusLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($status, CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId());
                        }
                        if (isset($this->_formValues['inex_study_status_id']) && $this->_formValues['inex_study_status_id'] == 1) {
                            $filters[] = $searchTxt . "not one of " . implode(", ", $statusLabels);
                        } else {
                            $filters[] = $searchTxt . "one of " . implode(", ", $statusLabels);
                        }
                        break;
                    case "eligibility_status_id":
                        $eligibilityLabels = [];
                        foreach ($this->_formValues[$searchFilter] as $eligibilityStatus) {
                            $eligibilityLabels[] = CRM_Nihrbackbone_Utils::getOptionValueLabel($eligibilityStatus, CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId());
                        }
                        if (isset($this->_formValues['inex_eligibility_status_id']) && $this->_formValues['inex_eligibility_status_id'] == 1) {
                            $filters[] = $searchTxt . "not one of " . implode(", ", $eligibilityLabels);
                        } else {
                            $filters[] = $searchTxt . "one of " . implode(", ", $eligibilityLabels);
                        }
                        break;
                    case "age_from":
                        if (isset($this->_formValues['inex_age']) && $this->_formValues['inex_age'] == 1) {
                            $filters[] = $searchTxt . "is less than " . $this->_formValues["age_from"];
                        } else {
                            $filters[] = $searchTxt . "is greater than or equal to " . $this->_formValues["age_from"];
                        }
                        break;
                    case "age_to":
                        if (isset($this->_formValues['inex_age']) && $this->_formValues['inex_age'] == 1) {
                            $filters[] = $searchTxt . "is greater than " . $this->_formValues["age_to"];
                        } else {
                            $filters[] = $searchTxt . "is less than or equal to " . $this->_formValues["age_to"];
                        }
                        break;
                    case "has_email":
                        if ($this->_formValues['has_email']) {
                            $filters[] = "has primary email";
                        }
                        break;
                    case "has_mobile":
                        if ($this->_formValues['has_mobile']) {
                            $filters[] = "has mobile phone";
                        }
                        break;
                    case "invite_date_from":
                        if (isset($this->_formValues['inex_invite_date']) && $this->_formValues['inex_invite_date'] == 1) {
                            $filters[] = $searchTxt . "is less than " . $this->_formValues["invite_date_from"];
                        } else {
                            $filters[] = $searchTxt . "is greater than or equal to " . $this->_formValues["invite_date_from"];
                        }
                        break;
                    case "invite_date_to":
                        if (isset($this->_formValues['inex_invite_date']) && $this->_formValues['inex_invite_date'] == 1) {
                            $filters[] = $searchTxt . "is greater than " . $this->_formValues["invite_date_to"];
                        } else {
                            $filters[] = $searchTxt . "is less than or equal to " . $this->_formValues["invite_date_to"];
                        }
                        break;
                    default:
                        $inex = "inex_" . $searchFilter;
                        if (is_array($this->_formValues[$searchFilter])) {
                            $textLabels = [];
                            foreach ($this->_formValues[$searchFilter] as $value) {
                                $textLabels[] = $value;
                            }
                            if (isset($this->_formValues[$inex]) && $this->_formValues[$inex] == 1) {
                                $filters[] = $searchTxt . "not one of " . implode(", ", $textLabels);
                            } else {
                                $filters[] = $searchTxt . "one of " . implode(", ", $textLabels);
                            }
                        } else {
                            if (isset($this->_formValues[$inex]) && $this->_formValues[$inex] == 1) {
                                $filters[] = $searchTxt . "is not " . $this->_formValues[$searchFilter];
                            } else {
                                $filters[] = $searchTxt . "is " . $this->_formValues[$searchFilter];
                            }
                        }
                        break;
                }
            }
        }
        if ($this->_studyId) {
            if (CRM_Nihrbackbone_NbrStudy::hasNoActionStatus((int)$this->_studyId)) {
                return ['summary' => "Filter(s) " . implode(" and ", $filters) . ", no volunteer actions allowed based on study status."];
            }
        }
        return ['summary' => "Filter(s) " . implode(" and ", $filters)];
    }

    /**
     * Get a list of displayable columns
     *
     * @return array, keys are printable column headers and values are SQL column names
     */
    function &columns()
    {
        // return by reference
        $columns = [
            E::ts('Name') => 'sort_name',
            E::ts('Study/Part ID') => 'nvpd_study_participant_id',
            E::ts('Part. ID') => 'participant_id',
            E::ts("BioResource ID") => 'bioresource_id',
            E::ts('Email') => 'email',
            E::ts('Gndr') => 'gender',
            E::ts('Age') => 'birth_date',
            E::ts('Ethn.') => 'ethnicity',
            E::ts('Loc.') => 'volunteer_address',
            E::ts('Tag(s)') => 'volunteer_tags',
            E::ts('Distance') => 'nvpd_distance_volunteer_to_study_centre',
            E::ts('Eligibility') => 'nvpd_eligible_status_id',
            E::ts('Recall Group') => 'recall_groups',
            E::ts('Status') => 'study_status',
            E::ts('Inv. Date') => 'nvpd_date_invited',
            E::ts('Researcher Date') => 'date_researcher',
            E::ts('Latest Visit Date') => 'latest_visit_date',
            E::ts('Case ID') => 'case_id',
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
    function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE)
    {
        // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
        return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    }

    /**
     * Construct a SQL SELECT clause
     *
     * @return string, sql fragment with SELECT arguments
     */
    function select()
    {
        // todo add participant and bioresource ID
        $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
        $studyParticipantIDColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
        $dateInvitedColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
        $distanceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_distance_volunteer_to_study_centre', 'column_name');
        $participantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'column_name');
        $bioresourceIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id', 'column_name');
        return "
      DISTINCT(contact_a.id) AS contact_id, cas.id AS case_id, contact_a.sort_name, contact_a.birth_date, genderov.label AS gender,
      ethnicov.label AS ethnicity, adr.city AS volunteer_address, nvpd." . $eligibleColumn . ", nvpd." . $studyParticipantIDColumn
            . ", stustatus.label AS study_status, '' AS recall_groups, nvpd."
            . $dateInvitedColumn . ", nvpd." . $distanceColumn . ", '' AS date_researcher, '' AS latest_visit_date,
      '' AS volunteer_tags, nvi." . $participantIdColumn . " AS participant_id, nvi." . $bioresourceIdColumn
            . " AS bioresource_id, em.email AS email";
    }

    /**
     * Construct a SQL FROM clause
     *
     * @return string, sql fragment with FROM and JOIN clauses
     */
    function from()
    {
        $nvgoTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
        $nvpdTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
        $nviTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('table_name');
        $genderOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId();
        $ethnicityOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId();
        $studyStatusOptionGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId();
        $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
        $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
        $hasEmail = $this->_formValues['has_email'];
        $hasEmailLeft = ($hasEmail == 1) ? "" : "LEFT";

        $hasMobile = $this->_formValues['has_mobile'];

        $hasPhoneSql = ($hasMobile == 1) ? "" : "LEFT";

        $from = "
    FROM civicrm_contact AS contact_a " .
      $hasEmailLeft . " JOIN civicrm_email AS em ON contact_a.id = em.contact_id AND em.is_primary = TRUE " .
      $hasPhoneSql . " JOIN civicrm_phone AS phone ON contact_a.id = phone.contact_id AND phone.phone_type_id=2
      JOIN civicrm_case_contact AS ccc ON contact_a.id = ccc.contact_id
      JOIN civicrm_case AS cas ON ccc.case_id = cas.id AND cas.is_deleted = 0
      LEFT JOIN " . $nvgoTable . " AS nvgo ON ccc.contact_id = nvgo.entity_id
      LEFT JOIN civicrm_address AS adr ON contact_a.id = adr.contact_id AND adr.is_primary = 1
      JOIN " . $nvpdTable . " AS nvpd ON cas.id = nvpd.entity_id
      JOIN " . $nviTable . " AS nvi ON contact_a.id = nvi.entity_id
      LEFT JOIN civicrm_option_value AS genderov ON contact_a.gender_id = genderov.value AND genderov.option_group_id = " . $genderOptionGroupId . "
      LEFT JOIN civicrm_option_value AS ethnicov ON nvgo." . $ethnicityColumn . " = ethnicov.value AND ethnicov.option_group_id = " . $ethnicityOptionGroupId . "
      JOIN civicrm_option_value AS stustatus ON nvpd." . $studyStatusColumn . " = stustatus.value AND stustatus.option_group_id = " . $studyStatusOptionGroupId . "
      LEFT JOIN civicrm_nbr_recall_group AS rcgrp ON cas.id = rcgrp.case_id";

        return $from;
    }

    /**
     * Construct a SQL WHERE clause
     *
     * @param bool $includeContactIDs
     * @return string, sql fragment with conditional expressions
     */
    function where($includeContactIDs = FALSE)
    {
        $clauses = [];
        $params = [1 => ["%nihr_volunteer%", "String"]];
        $index = 1;
        $where = "contact_a.contact_sub_type LIKE %1";
        $this->addEqualsClauses($index, $clauses, $params);
        $this->addLikeClauses($index, $clauses, $params);
        $this->addDateRangeClauses($index, $clauses, $params);
        if (!empty($clauses)) {
            $where .= ' AND ' . implode(' AND ', $clauses);
        }
        $this->addMultipleClauses($index, $where, $params);
        if (isset($this->_formValues['has_email']) && $this->_formValues['has_email'] == "2") {
            $where .= " AND em.email IS NULL";
        }

        if (isset($this->_formValues['has_mobile']) && $this->_formValues['has_mobile'] == "2") {
            $where .= " AND phone.phone IS NULL";
        }
        return $this->whereClause($where, $params);
    }

    /**
     * Method to add multiple clauses
     *
     * @param $index
     * @param $where
     * @param $params
     */
    private function addMultipleClauses(&$index, &$where, &$params)
    {
        $multipleFields = ['gender_id', 'ethnicity_id', 'recall_group', 'study_status_id', 'eligibility_status_id', 'tags'];
        foreach ($multipleFields as $multipleField) {
            $clauses = [];
            if (isset($this->_formValues[$multipleField]) && !empty($this->_formValues[$multipleField])) {
                $operator = $this->getOperator($multipleField, "=");
                switch ($multipleField) {
                    case 'gender_id':
                        foreach ($this->_formValues[$multipleField] as $multipleValue) {
                            $index++;
                            $clauses[] = "contact_a.gender_id " . $operator . " %" . $index;
                            $params[$index] = [(int)$multipleValue, "Integer"];
                        }
                        if (!empty($clauses)) {
                            if ($operator == "=") {
                                $where .= " AND (" . implode(" OR ", $clauses) . ")";
                            } else {
                                $where .= " AND (" . implode(" AND ", $clauses) . ")";
                            }
                        }
                        break;

                    case 'ethnicity_id':
                        $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
                        foreach ($this->_formValues[$multipleField] as $multipleValue) {
                            $index++;
                            $clauses[] = "nvgo." . $ethnicityColumn . " " . $operator . " %" . $index;
                            $params[$index] = [$multipleValue, "String"];
                        }
                        if (!empty($clauses)) {
                            if ($operator == "=") {
                                $where .= " AND (" . implode(" OR ", $clauses) . ")";
                            } else {
                                $where .= " AND (" . implode(" AND ", $clauses) . ")";
                            }
                        }
                        break;

                    case 'recall_group':
                        foreach ($this->_formValues[$multipleField] as $multipleValue) {
                            $index++;
                            $clauses[] = "rcgrp.recall_group " . $operator . " %" . $index;
                            $params[$index] = [$multipleValue, "String"];
                        }
                        if (!empty($clauses)) {
                            if ($operator == "=") {
                                $where .= " AND (" . implode(" OR ", $clauses) . ")";
                            } else {
                                $where .= " AND (" . implode(" AND ", $clauses) . ")";
                            }
                        }
                        break;

                    case 'study_status_id':
                        $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
                        foreach ($this->_formValues[$multipleField] as $multipleValue) {
                            $index++;
                            $clauses[] = "nvpd." . $statusColumn . " " . $operator . " %" . $index;
                            $params[$index] = [$multipleValue, "String"];
                        }
                        if (!empty($clauses)) {
                            if ($operator == "=") {
                                $where .= " AND (" . implode(" OR ", $clauses) . ")";
                            } else {
                                $where .= " AND (" . implode(" AND ", $clauses) . ")";
                            }
                        }
                        break;

                    case 'eligibility_status_id':
                        $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
                        foreach ($this->_formValues[$multipleField] as $multipleValue) {
                            $index++;
                            $clauses[] = "nvpd." . $eligibleColumn . " " . $operator . " %" . $index;
                            $params[$index] = [$multipleValue, "String"];
                        }
                        if (!empty($clauses)) {
                            if ($operator == "=") {
                                $where .= " AND (" . implode(" OR ", $clauses) . ")";
                            } else {
                                $where .= " AND (" . implode(" AND ", $clauses) . ")";
                            }
                        }
                        break;

                    case 'tags':
                        $tagIds = [];
                        foreach ($this->_formValues['tags'] as $key => $tagId) {
                            $tagIds[] = $tagId;
                        }
                        if (!empty($tagIds)) {
                            $where .= " AND ccc.contact_id " . $this->getOperator('tags', 'IN') . " (SELECT entity_id FROM civicrm_entity_tag WHERE entity_table = 'civicrm_contact' AND tag_id IN(" . implode(",", $tagIds) . "))";
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
    private function addEqualsClauses(&$index, &$clauses, &$params)
    {
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
    private function addDateRangeClauses(&$index, &$clauses, &$params)
    {
        $inviteDateColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
        $rangeFields = ['age', 'invite_date'];
        foreach ($rangeFields as $rangeField) {
            $fromField = $rangeField . "_from";
            $toField = $rangeField . "_to";
            switch ($rangeField) {
                case 'age':
                    // if both filled and operator is exclude, add one clause
                    if (isset($this->_formValues[$fromField]) && !empty($this->_formValues[$fromField])
                        && isset($this->_formValues[$toField]) && !empty($this->_formValues[$toField]
                            && $this->_formValues['inex_age'] == 1)) {
                        $index++;
                        $clause = "(TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) < %" . $index;
                        $params[$index] = [$this->_formValues[$fromField], "String"];
                        $index++;
                        $clause .= " OR TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) > %" . $index . ")";
                        $params[$index] = [$this->_formValues[$toField], "String"];
                        $clauses[] = $clause;
                    } else {
                        if (isset($this->_formValues[$fromField]) && !empty($this->_formValues[$fromField])) {
                            $index++;
                            if (isset($this->_formValues['inex_age']) && $this->_formValues['inex_age'] == 1) {
                                $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) < %" . $index;
                            } else {
                                $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) >= %" . $index;
                            }
                            $params[$index] = [$this->_formValues[$fromField], "String"];
                        }
                        if (isset($this->_formValues[$toField]) && !empty($this->_formValues[$toField])) {
                            $index++;
                            if (isset($this->_formValues['inex_age']) && $this->_formValues['inex_age'] == 1) {
                                $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) > %" . $index;
                            } else {
                                $clauses[] = "TIMESTAMPDIFF(YEAR, contact_a.birth_date, CURDATE()) <= %" . $index;
                            }
                            $params[$index] = [$this->_formValues[$toField], "String"];
                        }
                    }
                    break;

                case 'invite_date':
                    // if both filled and operator is exclude, add one clause
                    if (isset($this->_formValues[$fromField]) && !empty($this->_formValues[$fromField])
                        && isset($this->_formValues[$toField]) && !empty($this->_formValues[$toField]
                            && $this->_formValues['inex_invite_date'] == 1)) {
                        $index++;
                        $clause = "(nvpd." . $inviteDateColumn . " < %" . $index;
                        $params[$index] = [$this->_formValues[$fromField], "String"];
                        $index++;
                        $clause .= " OR nvpd." . $inviteDateColumn . " > %" . $index . ")";
                        $params[$index] = [$this->_formValues[$toField], "String"];
                        $clauses[] = $clause;
                    } else {
                        if (isset($this->_formValues[$fromField]) && !empty($this->_formValues[$fromField])) {
                            $index++;
                            if (isset($this->_formValues['inex_invite_date']) && $this->_formValues['inex_invite_date'] == 1) {
                                $clauses[] = "nvpd." . $inviteDateColumn . " < %" . $index;
                            } else {
                                $clauses[] = "nvpd." . $inviteDateColumn . " >= %" . $index;
                            }
                            $params[$index] = [$this->_formValues[$fromField], "String"];
                        }
                        if (isset($this->_formValues[$toField]) && !empty($this->_formValues[$toField])) {
                            $index++;
                            if (isset($this->_formValues['inex_invite_date']) && $this->_formValues['inex_invite_date'] == 1) {
                                $clauses[] = "nvpd." . $inviteDateColumn . " > %" . $index;
                            } else {
                                $clauses[] = "nvpd." . $inviteDateColumn . " <= %" . $index;
                            }
                            $params[$index] = [$this->_formValues[$toField], "String"];
                        }
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
    private function addLikeClauses(&$index, &$clauses, &$params)
    {
        $likeFields = ['first_name', 'last_name', 'study_participant_id', 'participant_id', 'bioresource_id'];
        $studyPartColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
        $participantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'column_name');
        $bioresourceIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id', 'column_name');
        foreach ($likeFields as $likeField) {
            if (isset($this->_formValues[$likeField]) && !empty($this->_formValues[$likeField])) {
                $index++;
                $params[$index] = ["%" . $this->_formValues[$likeField] . "%", "String"];
                switch ($likeField) {
                    case 'bioresource_id':
                        $clauses[] = "nvi." . $bioresourceIdColumn . " " . $this->getOperator($likeField, "LIKE") . " %" . $index;
                        break;

                    case 'participant_id':
                        $clauses[] = "nvi." . $participantIdColumn . " " . $this->getOperator($likeField, "LIKE") . " %" . $index;
                        break;

                    case 'study_participant_id':
                        $clauses[] = "nvpd." . $studyPartColumn . " " . $this->getOperator($likeField, "LIKE") . " %" . $index;
                        break;

                    default:
                        $clauses[] = "contact_a." . $likeField . " " . $this->getOperator($likeField, "LIKE") . " %" . $index;
                        break;
                }
            }
        }
    }

    /**
     * Method to determine operator -> include or exclude
     * @param $fieldName
     * @param $operatorBase
     * @return string
     */
    private function getOperator($fieldName, $operatorBase)
    {
        $operatorField = "inex_" . $fieldName;
        switch ($operatorBase) {
            case "IN":
            case "LIKE":
                if (isset($this->_formValues[$operatorField]) && $this->_formValues[$operatorField] == 1) {
                    return " NOT " . $operatorBase;
                }
                break;
            case "=":
                if (isset($this->_formValues[$operatorField]) && $this->_formValues[$operatorField] == 1) {
                    return "<>";
                }
        }
        return $operatorBase;
    }

    /**
     * Determine the Smarty template for the search screen
     *
     * @return string, template path (findable through Smarty template path)
     */
    function templateFile()
    {
        return 'CRM/Nbrprojectvolunteerlist/Form/Search/VolunteerList.tpl';
    }

    /**
     * Modify the content of each row
     *
     * @param array $row modifiable SQL result row
     * @return void
     * @throws
     */
    function alterRow(&$row)
    {
        foreach ($row as $fieldName => &$field) {
            switch ($fieldName) {
                case 'birth_date':
                    $birthDate = $row[$fieldName];
                    $row[$fieldName] = "unknown";
                    if (!empty($birthDate)) {
                        if (!empty($row[$fieldName])) {
                            $age = CRM_Utils_Date::calculateAge($birthDate);
                            if (isset($age['years'])) {
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
                    $row['gender'] = substr($row['gender'], 0, 1);
                    break;

                case 'latest_visit_date':
                    $latestVisitDate = CRM_Nihrbackbone_NbrVolunteerCase::getNearestVisit($row['case_id']);
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
                    if (in_array($row['study_status'], $this->_eligibleParticipationStatus)) {
                        if (empty($row[$fieldName])) {
                            $row[$fieldName] = "Eligible";
                        } else {
                            $row[$fieldName] = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($row[$fieldName]));
                        }
                    } else {
                        $row[$fieldName] = "";
                    }
                    break;

                case 'volunteer_tags':
                    $vol = new CRM_Nbrprojectvolunteerlist_NbrVolunteer();
                    $tags = $vol->getContactTags($row['contact_id']);
                    if (strlen($tags) > 80) {
                        $tags = substr($tags, 0, 77) . '...';
                    }
                    $row['volunteer_tags'] = $tags;
                    break;

                case 'recall_groups':
                    $recallGroups = CRM_Nihrbackbone_BAO_NbrRecallGroup::getRecallGroupsForCase($row['case_id']);
                    $row['recall_groups'] = implode(", ", $recallGroups);
                    if (strlen($row['recall_groups']) > 80) {
                        $row['recall_groups'] = substr($row['recall_groups'], 0, 77) . "...";
                    }
                    break;
            }
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
    protected function getEntityDefaults($entity)
    {
        $defaults = [];
        // if the study id is in the request, start the search with this study
        if ($this->_force) {
            $this->_studyId = CRM_Utils_Request::retrieveValue('sid', 'Integer');
            if ($this->_studyId) {
                $defaults['study_id'] = $this->_studyId;
            }
        }
        return $defaults;
    }

    /**
     * Method to get the selectedIds if necessary
     */
    public function getSelectedIds()
    {
        $selectedIds = [];
        $qfKeyParam = CRM_Utils_Array::value('qfKey', $this->_formValues);
        if ($qfKeyParam) {
            $qfKeyParam = "civicrm search {$qfKeyParam}";
            $selectedIdsArr = Civi::service('prevnext')->getSelection($qfKeyParam, 'get');
            if (isset($selectedIdsArr[$qfKeyParam]) && is_array($selectedIdsArr[$qfKeyParam])) {
                $selectedIds = array_keys($selectedIdsArr[$qfKeyParam]);
            }
        }
        return $selectedIds;
    }

}

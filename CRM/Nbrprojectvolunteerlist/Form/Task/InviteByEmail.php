<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the invite by email action for a volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 6 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_InviteByEmail extends CRM_Contact_Form_Task {

  private $_countInvited = NULL;
  private $_countInvalid = NULL;
  private $_invited = [];
  private $_invalids = [];
  private $_projectId = NULL;

  /**
   * Method to get the invited data for the selected contact IDs
   *
   */
  private function getInvitedData() {
    $this->_invited = [];
    $this->_invalids = [];
    $this->_countInvited = 0;
    $this->_countInvalid = 0;
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $bioresourceIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id', 'column_name');
    $participantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'column_name');
    $nviTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('table_name');
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, nvi." . $bioresourceIdColumn . " AS bioresource_id, 
        nvi." . $participantIdColumn . " AS participant_id, cvnpd." . $studyParticipantColumn . " AS study_participant_id, cvnpd. "
      . $eligiblesColumn. " AS eligible_status_id, ce.email
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_email AS ce ON vol.id = ce.contact_id AND ce.is_primary = %1
        LEFT JOIN " . $nviTable . " AS nvi ON vol.id = nvi.entity_id
        WHERE cvnpd." . $projectColumn . " = %2 AND cas.is_deleted = %3 AND vol.id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [(int)$this->_projectId, "Integer"],
      3 => [0, "Integer"],
    ];
    $i = 3;
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
        'participant_id' => $dao->participant_id,
        'study_participant_id' => $dao->study_participant_id,
        'email' => $dao->email,
      ];
      $eligibleStatus = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id));
      if (!empty($eligibleStatus)) {
        $volunteer['eligible_status'] = $eligibleStatus;
      }
      else {
        $volunteer['eligible_status'] = "Eligible";
      }
      // add if valid email else list as invalid
      if (filter_var($dao->email, FILTER_VALIDATE_EMAIL)) {
        $this->_countInvited++;
        $this->_invited[$dao->contact_id] = $volunteer;
      }
      else {
        $this->_countInvalid++;
        $this->_invalids[$dao->contact_id] = $volunteer;
      }
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['project_id'])) {
      $this->_projectId = self::$_searchFormValues['project_id'];
    }
    $this->add('select', 'template_id', E::ts('Message template for email'), $this->getTemplateList(),
      TRUE, ['class' => 'crm-select2']);
    $this->assign('template_txt', E::ts('Template used for invitation'));
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by email:') . $this->_countInvited);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be invited because their email is invalid:') . $this->_countInvalid);
    $this->getInvitedData();
    $this->assign('count_invited_txt', E::ts('Number of volunteers that will be invited: ') . $this->_countInvited);
    $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be invited: ') . $this->_countInvalid);
    $this->assign('invited', $this->_invited);
    $this->assign('invalids', $this->_invalids);
    $this->addDefaultButtons(ts('Invite by Email'));
  }

  /**
   * Method to get all non-workflow active message templates
   *
   * @return array
   */
  private function getTemplateList() {
    $templates = [];
    try {
      $result = civicrm_api3('MessageTemplate', 'get', [
        'return' => ["id", "msg_title"],
        'is_active' => 1,
        'options' => ['limit' => 0],
        'workflow_id' => ['IS NULL' => 1],
      ]);
      foreach ($result['values'] as $msgTemplateId => $msgTemplate) {
        $templates[$msgTemplateId] = $msgTemplate['msg_title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $templates;
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a project, contactIds and a template
    if (isset($this->_projectId) && !empty($this->_contactIds) && !empty($this->_submitValues['template_id'])) {
      // first find all relevant cases
      $caseIds = $this->getRelevantCaseIds();
      // then send email (include case_id so the activity is recorded)
      foreach ($caseIds as $caseId => $contactId) {
        try {
          civicrm_api3('Email', 'send', [
            'template_id' => $this->_submitValues['template_id'],
            'contact_id' => $contactId,
            'case_id' => $caseId,
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->warning(E::ts("Could not send invitation to project with ID ") . $this->_projectId
            . E::ts(" in ") . __METHOD__ . E::ts(", error from API Email Send: ") . $ex->getMessage());
        }
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
    $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $query = "SELECT ccc.case_id, ccc.contact_id
        FROM " . $participationTable. " AS cvnpd
        LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        WHERE cvnpd." . $projectColumn . " = %2 AND ccc.contact_id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [$this->_projectId, "Integer"],
      ];
    $i = 2;
    $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($this->_contactIds, $i, $queryParams);
    $query .= implode("," , $elements) . ")";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $caseIds[$dao->case_id] = $dao->contact_id;
    }
    return $caseIds;
  }
}


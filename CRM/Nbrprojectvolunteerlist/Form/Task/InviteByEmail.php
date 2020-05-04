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
  private $_studyId = NULL;

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
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnpd." . $studyParticipantColumn
        . " AS study_participant_id, cvnpd." . $eligiblesColumn. " AS eligible_status_id,
        ce.email
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_email AS ce ON vol.id = ce.contact_id AND ce.is_primary = %1
        WHERE cvnpd." . $studyColumn . " = %2 AND cas.is_deleted = %3 AND vol.id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [(int)$this->_studyId, "Integer"],
      3 => [0, "Integer"],
    ];
    $i = 3;
    CRM_Nbrprojectvolunteerlist_Utils::addContactIdsToQuery($i, $this->_contactIds, $query, $queryParams);
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $volunteer = [
        'display_name' => $dao->display_name,
        'study_participant_id' => $dao->study_participant_id,
        'email' => $dao->email,
      ];
      $eligibleStatus = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id));
      $volunteer['eligible_status'] = $eligibleStatus;
      // only allow invite if eligible
      if ($this->isEligible($dao->eligible_status_id)) {
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
      else {
        $this->_countInvalid++;
        $this->_invalids[$dao->contact_id] = $volunteer;
      }
    }
  }

  /**
   * Check if status of volunteer is eligible
   *
   * @param $statusId
   * @return bool
   */
  private function isEligible($statusId) {
    if (empty($statusId)) {
      return FALSE;
    }
    $parts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $statusId);
    foreach ($parts as $key => $value) {
      if (empty($value)) {
        unset($parts[$key]);
      }
    }
    if (count($parts) == 1) {
      $singleStatus = implode("", $parts);
      if ($singleStatus == Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->add('select', 'template_id', E::ts('Message template for email'), $this->getTemplateList(),
      TRUE, ['class' => 'crm-select2']);
    $this->assign('template_txt', E::ts('Template used for invitation'));
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by email:') . $this->_countInvited);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be invited because their email is invalid or because they are not eligible:') . $this->_countInvalid);
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
    // only if we have a study, contactIds and a template
    if (isset($this->_studyId) && !empty($this->_contactIds) && !empty($this->_submitValues['template_id'])) {
      // first find all relevant cases
      $caseIds = $this->getRelevantCaseIds();
      // then send email (include case_id so the activity is recorded) and add an invited activity
      foreach ($caseIds as $caseId => $contactId) {
        try {
          civicrm_api3('Email', 'send', [
            'template_id' => $this->_submitValues['template_id'],
            'contact_id' => $contactId,
            'case_id' => $caseId,
          ]);
          // now add the invite activity
          CRM_Nihrbackbone_NbrInvitation::addInviteActivity($caseId, $contactId, $this->_studyId, "Invite By Email Action");
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->warning(E::ts("Could not send invitation to study ") . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId)
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
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $query = "SELECT ccc.case_id, ccc.contact_id
        FROM " . $participationTable. " AS cvnpd
        LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        LEFT JOIN civicrm_case AS cc ON ccc.case_id = cc.id
        WHERE cvnpd." . $studyColumn . " = %2 AND cc.is_deleted = %3 AND ccc.contact_id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [$this->_studyId, "Integer"],
      3 => [0, "Integer"],
      ];
    $i = 3;
    $contactIds = [];
    foreach ($this->_invited as $invitedId => $invitedData) {
      $contactIds[] = $invitedId;
    }
    $elements = CRM_Nbrprojectvolunteerlist_Utils::processContactQueryElements($contactIds, $i, $queryParams);
    $query .= implode("," , $elements) . ")";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $caseIds[$dao->case_id] = $dao->contact_id;
    }
    return $caseIds;
  }
}


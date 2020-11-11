<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the invite by pdf action for a volunteer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 10 Nov 2020
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_InviteByPdf extends CRM_Contact_Form_Task {

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
    $this->_countInvalid = 0;
    $this->_invalids = [];
    $this->_countInvited = 0;
    $dao = CRM_Nbrprojectvolunteerlist_Utils::getInvitedData($this->_studyId, $this->_contactIds);
    while ($dao->fetch()) {
      $volunteer = new CRM_Nbrprojectvolunteerlist_NbrVolunteer();
      $volunteer->classifyVolunteer("invite_pdf", $dao, $this->_invalids, $this->_countInvalid, $this->_invited, $this->_countInvited);
    }
  }


  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->add('select', 'template_id', E::ts('Message template for email'), CRM_Nbrprojectvolunteerlist_Utils::getTemplateList(),
      TRUE, ['class' => 'crm-select2']);
    $this->add('select', 'printer', E::ts('Printer'), ['printer1', 'printer2'],
      TRUE, ['class' => 'crm-select2']);
    $this->assign('template_txt', E::ts('Template used for invitation'));
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by PDF:') . $this->_countInvited);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be invited because they are not eligible:') . $this->_countInvalid);
    $this->getInvitedData();
    $this->assign('count_invited_txt', E::ts('Number of volunteers that will be invited: ') . $this->_countInvited);
    $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be invited: ') . $this->_countInvalid);
    $this->assign('invited', $this->_invited);
    $this->assign('invalids', $this->_invalids);
    $this->addDefaultButtons(ts('Invite by PDF'));
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a study, invited ids and a template
    if (isset($this->_studyId) && !empty($this->_invited) && !empty($this->_submitValues['template_id'])) {
      // first find all relevant cases
      $caseIds = $this->getRelevantCaseIds();
      // then send email (include case_id so the activity is recorded) and add an invited activity
      foreach ($caseIds as $caseId => $contactId) {
        try {
          civicrm_api3('Pdf', 'create', [
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
    if (!empty($this->_invited)) {
      $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
      $query = "SELECT ccc.case_id, ccc.contact_id
        FROM " . $participationTable. " AS cvnpd
        LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        LEFT JOIN civicrm_case AS cc ON ccc.case_id = cc.id
        WHERE cvnpd." . $studyColumn . " = %2 AND cc.is_deleted = %3 AND ccc.contact_id IN(";
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
      $query .= implode("," , $elements) . ") LIMIT 50";
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($dao->fetch()) {
        $caseIds[$dao->case_id] = $dao->contact_id;
      }
    }
    return $caseIds;
  }

}


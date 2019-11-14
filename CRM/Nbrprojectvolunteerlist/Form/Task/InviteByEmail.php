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
  private $_invited = [];
  private $_projectId = NULL;

  /**
   * Method to get the invited data for the selected contact IDs
   *
   */
  private function getInvitedData() {
    $this->_invited = [];
    $bioResourceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_bioresource_id', 'column_name');
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $aliasTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomGroup('table_name');
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnva." . $bioResourceColumn . " AS bioresource_id, 
        cvnpd." . $studyParticipantColumn . " AS study_participant_id, cvnpd. "
      . $eligiblesColumn. " AS eligible_status_id, ce.email
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_email AS ce ON vol.id = ce.contact_id AND ce.is_primary = %1
        LEFT JOIN " . $aliasTable . " AS cvnva ON vol.id = cvnva.entity_id
        WHERE cvnpd." . $projectColumn . " = %2 AND cas.is_deleted = %3";
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
        'study_participant_id' => $dao->study_participant_id,
        'eligible_status' => implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($dao->eligible_status_id)),
        'email' => $dao->email,
      ];
      $this->_invited[$dao->contact_id] = $volunteer;
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
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by email:'));
    $this->assign('count_invited_txt', E::ts('Number of volunteers that will be invited: ') . $this->_countInvited);
    $this->getInvitedData();
    $this->assign('invited', $this->_invited);
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
    $values = $this->exportValues();
  }

}


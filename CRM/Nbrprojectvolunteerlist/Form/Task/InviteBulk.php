<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the invite by bulk mail action
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Sep 2020
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_InviteBulk extends CRM_Contact_Form_Task {

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
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnpd." . $studyParticipantColumn
        . " AS study_participant_id, cvnpd." . $eligiblesColumn. " AS eligible_status_id,
        ce.email, cvnpd." . $studyStatusColumn . " AS study_participation_status
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_email AS ce ON vol.id = ce.contact_id AND ce.is_primary = %1 AND ce.on_hold = 0
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
      $volunteer = new CRM_Nbrprojectvolunteerlist_NbrVolunteer();
      $volunteer->classifyVolunteer("mailing", $dao, $this->_invalids, $this->_countInvalid, $this->_invited, $this->_countInvited);
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->add('select', 'template_id', E::ts('Message template for bulk mail'), CRM_Nbrprojectvolunteerlist_Utils::getTemplateList(),
      TRUE, ['class' => 'crm-select2']);
    $this->add('text', 'subject', E::ts("Subject for mailing"), ['size' => 'HUGE'], TRUE);
    $this->add('text', 'from_name', E::ts("Mailing from name"), [], TRUE);
    $this->add('text', 'from_email', E::ts('Mailing from email'), [], TRUE);
    $this->addRule('from_email',E::ts('Has to be a valid email address.'),'email');
    $this->add('datepicker', 'scheduled_date', E::ts('Schedule mailing for date'), [],TRUE, ['time' => FALSE]);
    $this->assign('template_txt', E::ts('Template used for invitation mailing'));
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by this mailing:') . $this->_countInvited);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be invited with reason:') . $this->_countInvalid);
    $this->getInvitedData();
    $this->assign('count_invited_txt', E::ts('Number of volunteers that will be invited: ') . $this->_countInvited);
    $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be invited: ') . $this->_countInvalid);
    $this->assign('invited', $this->_invited);
    $this->assign('invalids', $this->_invalids);
    $this->addDefaultButtons(ts('Schedule invitation mailing'));
  }

  /**
   * Method to set default values
   *
   * @return array|mixed
   */
  public function setDefaultValues() {
    $nowDate = new DateTime;
    $defaults['scheduled_date'] = $nowDate->format('Y-m-d');
    return $defaults;
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a study, invited ids and a template
    if (isset($this->_studyId) && !empty($this->_invited)) {
      // first create temporary group
      try {
        $group = civicrm_api3('Group', 'create', CRM_Nbrprojectvolunteerlist_Utils::createInviteBulkGroupParams());
        // next add all volunteers to invite to group
        $this->addVolunteersToGroup($group['id']);
        $this->createMailing($group['id']);
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->error('Could not create a temporary group for bulk invite in '
          . __METHOD__ . ', error message from API Group create: ' . $ex->getMessage());
        CRM_Core_Session::setStatus("Could not create a group for invite by bulk, please contact IT support.", "Can not execute bulk invite", "error");
      }
    }
  }

  /**
   * Method to create the mailing for the bulk invite
   *
   * @param $groupId
   */
  private function createMailing($groupId) {
    try {
      $mailing = civicrm_api3('Mailing', 'create', CRM_Nbrprojectvolunteerlist_Utils::createInviteMailingParams($groupId));
      // insert record to link mailing id and group id
      $this->createNbrMailing($groupId, $mailing['id']);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error('Could not create a mailing for bulk invite in '
        . __METHOD__ . ', error message from API Mailing create: ' . $ex->getMessage());
      CRM_Core_Session::setStatus("Could not create  a mailing for invite by bulk, please contact IT support.", "Can not execute bulk invite", "error");
    }
  }

  /**
   * Method to create an NbrMailing to link group and mailing so further processing can be done when
   *  mailing has been completed
   *
   * @param $groupId
   * @param $mailingId
   */
  private function createNbrMailing($groupId, $mailingId) {
    try {
      civicrm_api3('NbrMailing', 'create', [
        'mailing_type' => "invite",
        'mailing_id' => $mailingId,
        'group_id' => $groupId,
      ]);
      CRM_Core_Session::setStatus("Mailing for invite by bulk scheduled successfully.", "Bulk invite mailing scheduled", "success");
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error('Could not create a record linking the bulk invite mailing with ID ' . $mailingId .
        ' and group with ID ' . $groupId. '. Mailing has been successfully scheduled but the temporary group will not be deleted nor will the following actions be processed: change invite date, add activity to case, change participant status. Error message from API NbrMailing create: ' . $ex->getMessage());
      CRM_Core_Session::setStatus("Mailing scheduled but invite activity processing not possible, please contact IT support.", "Can not execute post invite bulk mail process", "error");
    }
  }


}


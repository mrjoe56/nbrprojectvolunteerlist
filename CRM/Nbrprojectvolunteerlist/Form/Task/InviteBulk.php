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
   * Method to get the volunteer data for the selected contact IDs
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
      $volunteer->classifyVolunteer("invite_bulk", $dao, $this->_invalids, $this->_countInvalid, $this->_invited, $this->_countInvited);
    }
  }

  /**
   * Overridden parent method om formulier op te bouwen
   */
  public function buildQuickForm()   {
    if (isset(self::$_searchFormValues['study_id'])) {
      $this->_studyId = self::$_searchFormValues['study_id'];
    }
    $this->assign('invited_txt', E::ts('Volunteers that will be invited by this bulk mailing:') . $this->_countInvited);
    $this->assign('invalid_txt', E::ts('Volunteers that will NOT be invited with reason:') . $this->_countInvalid);
    $this->getInvitedData();
    $this->assign('count_invited_txt', E::ts('Number of volunteers that will be invited: ') . $this->_countInvited);
    $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be invited: ') . $this->_countInvalid);
    $this->assign('invited', $this->_invited);
    $this->assign('invalids', $this->_invalids);
    $this->addDefaultButtons('Create draft invitation mailing');
  }

  /**
   * Overridden parent method
   */
  public function postProcess() {
    // only if we have a study, invited ids and a template
    if (isset($this->_studyId) && !empty($this->_invited)) {
      // change study status to invitation pending AND generate study participation ID (issue 7505)
      $this->changeStatusInvitationPending();
      // first create temporary group
      try {
        $group = civicrm_api3('Group', 'create', CRM_Nbrprojectvolunteerlist_Utils::createInviteBulkGroupParams($this->_studyId));
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
   * Method to change the invitation pending status for each invite
   */
  private function changeStatusInvitationPending() {
    $status = Civi::service('nbrBackbone')->getInvitationPendingParticipationStatusValue();
    foreach ($this->_invited as $invitedContactId => $invitedData) {
      $caseId = CRM_Nihrbackbone_NbrVolunteerCase::getActiveParticipationCaseId($this->_studyId, $invitedContactId);
      if ($caseId) {
        // issue 7505: generate the study participation ID because it might be required in the invite mail!
        CRM_Nihrnumbergenerator_StudyParticipantNumberGenerator::createNewNumberForCase($caseId);
        CRM_Nihrbackbone_NbrVolunteerCase::updateStudyStatus($caseId, $invitedContactId, $status);
      }
      else {
        Civi::log()->warning("Could not find a case ID for volunteer with ID " . $invitedContactId . " in study "
          . $this->_studyId . ", volunteer status NOT set to Invitation Pending!");
      }
    }
  }


  /**
   * Method to add volunteer to temporary group for bulk invite
   *
   * @param $groupId
   */
  private function addVolunteersToGroup($groupId) {
    foreach ($this->_invited as $invitedContactId => $invitedData) {
      try {
        civicrm_api3('GroupContact', 'create', [
          'group_id' => $groupId,
          'contact_id' => $invitedContactId,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->error('Could not add contact with ID ' . $invitedContactId . ' to mailing group for bulk invite with ID '
          . $groupId . ' in ' . __METHOD__ . ', error message from API GroupContact create: ' . $ex->getMessage());
        CRM_Core_Session::setStatus("Could not add volunteer with ID ' . $invitedContactId . ', will not be invited by mailing. Please correct manually.", "Can not add volunteer to bulk invite", "error");
      }

    }
  }

  /**
   * Method to create the mailing for the bulk invite
   *
   * @param $groupId
   */
  private function createMailing($groupId) {
    $mailingParams = CRM_Nbrprojectvolunteerlist_Utils::createMailingParams($this->_studyId, $groupId, $this->_submitValues, 'invite');
    try {
      $mailing = civicrm_api3('Mailing', 'create', $mailingParams);
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
        'nbr_mailing_type' => "invite",
        'mailing_id' => $mailingId,
        'group_id' => $groupId,
        'study_id' => $this->_studyId,
      ]);
      $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId);
      CRM_Core_Session::setStatus("Draft mailing for invite by bulk to " . $studyNumber . " successfully created.", "Draft Bulk invite mailing created", "success");
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error('Could not create a record linking the bulk invite mailing with ID ' . $mailingId .
        ' and group with ID ' . $groupId. '. Mailing has been successfully scheduled but the temporary group will not be deleted nor will the following actions be processed: change invite date, add activity to case, change participant status. Error message from API NbrMailing create: ' . $ex->getMessage());
      CRM_Core_Session::setStatus("Mailing scheduled but invite activity processing not possible, please contact IT support.", "Can not execute post invite bulk mail process", "error");
    }
  }


}


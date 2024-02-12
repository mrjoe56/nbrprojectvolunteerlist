<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class to process the invite by bulk mail action
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Sep 2020
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Form_Task_GenericBulkMailing extends CRM_Contact_Form_Task {

    private $_countSelected = NULL;
    private $_countInvalid = NULL;
    private $_selected = [];
    private $_invalids = [];
    private $_studyId = NULL;


    private function contactInfo($contactIds){

        $query= "select con.id  AS contact_id,  con.display_name AS display_name, em.email AS email from civicrm_contact AS con 
         LEFT JOIN civicrm_email AS em ON em.contact_id=con.id
         WHERE ";

        $index=1;
        $contactSize=sizeof($contactIds);
        $params=[];
        foreach ($contactIds as $id){
            $query.="con.id=%".$index;
            $params[$index]= [$id, "Integer"];
            $index++;
            if($index<=$contactSize){
                $query.=" OR ";
            }
        }
        $volunteerDataDao = CRM_Core_DAO::executeQuery($query, $params);
        return $volunteerDataDao;
    }
    /**
     * Method to get the volunteer data for the selected contact IDs
     *
     */
    private function getVolunteerData() {
        $this->_invited = [];
        $this->_countInvalid = 0;
        $this->_invalids = [];
        $this->_countInvited = 0;
        $dao=$this->contactInfo($this->_contactIds);
        while ($dao->fetch()) {
            $volunteer = new CRM_Nbrprojectvolunteerlist_NbrVolunteer();
            $volunteer->classifyVolunteer("bulkemailthing", $dao, $this->_invalids, $this->_countInvalid, $this->_selected, $this->_countSelected);
        }
    }

    /**
     * Overridden parent method om formulier op te bouwen
     */
    public function buildQuickForm()   {

        $this->add('text', 'mailing_name', E::ts("Title"), ['maxlength'=>64], TRUE);
        $this->assign('selected_txt', E::ts('Volunteers that will be mailed by this bulk mailing:') . $this->_countSelected);
        $this->assign('invalid_txt', E::ts('Volunteers that will NOT be mailed with reason:') . $this->_countInvalid);
        $this->getVolunteerData();

        $this->assign('count_selected_txt', E::ts('Number of volunteers that will be mailed: ') . $this->_countSelected);
        $this->assign('count_invalid_txt', E::ts('Number of volunteers that will NOT be mailed: ') . $this->_countInvalid);
        $this->assign('volunteers', $this->_selected);
        $this->assign('invalids', $this->_invalids);
        $this->addDefaultButtons('Create draft mailing');
    }


    /**
     * Method to create the params for the temporary group used by Invite by Bulk
     *
     * @return array
     */
    private function createBulkGroupParams() {
        $now = new DateTime();

        return [
            'name' => "Temp mailing: ".$this->_submitValues['mailing_name'] ."'(". $now->format('Ymdhis').")",
            'title' => "Temp. bulk mailing group made on " . $now->format('Y-m-d H:i:s'),
            'description' => "This group is a temporary one used for bulk mailing study volunteers - do not update or use, will be removed automatically when mailing is completed.",
            'is_active' => 1,
            'visibility' => "User and User Admin Only",
            'group_type' => "Mailing List",
            'is_reserved' => 1,
            'created_id' => CRM_Core_Session::getLoggedInContactID()
        ];
    }
    /**
     * Overridden parent method
     */
    public function postProcess() {
        // only if we have a study, selected ids and a template
        if ( !empty($this->_selected)) {
            // first create temporary group
            try {
                $group = civicrm_api3('Group', 'create', $this->createBulkGroupParams());
                // next add all volunteers to invite to group
                $this->addVolunteersToGroup($group['id']);
                $this->createMailing($group['id']);
                CRM_Core_Session::setStatus("Draft bulk mailing successfully created.", "Draft Bulk mailing created", "success");
            }
            catch (CiviCRM_API3_Exception $ex) {
                Civi::log()->error('Could not create a temporary group for bulk mailing in '
                    . __METHOD__ . ', error message from API Group create: ' . $ex->getMessage());
                CRM_Core_Session::setStatus("Could not create a group for bulk mail, please contact IT support.", "Can not execute bulk mail from MSP", "error");
            }
        }
    }

    /**
     * Method to add volunteer to temporary group for bulk mail
     *
     * @param $groupId
     */
    private function addVolunteersToGroup($groupId) {
        foreach ($this->_selected as $selectedContactId => $selectedData) {
            try {
                civicrm_api3('GroupContact', 'create', [
                    'group_id' => $groupId,
                    'contact_id' => $selectedContactId,
                ]);
            }
            catch (CiviCRM_API3_Exception $ex) {
                Civi::log()->error('Could not add contact with ID ' . $selectedContactId . ' to mailing group for study bulk mail with ID '
                    . $groupId . ' in ' . __METHOD__ . ', error message from API GroupContact create: ' . $ex->getMessage());
                CRM_Core_Session::setStatus("Could not add volunteer with ID ' . $selectedContactId . ', will not be part of the study bulk mailing. Please correct manually.", "Can not add volunteer to study bulk mail", "error");
            }
        }
    }
    /**
     * Method to create array with bulk invite mailing params
     *
     * @param $studyId
     * @param $groupId
     * @param $formValues
     * @param $type (default "")
     * @return array|false
     */
    private function createMailingParams( $groupId, $formValues, $type = "") {
        if (empty($groupId)) {
            return FALSE;
        }
        $include = [$groupId];

        $mailingParams = [
            'name' =>"Bulk mailing: ". $this->_submitValues['mailing_name']. "(created " . date('d-m-Y') . ")",
            'groups' => ['include' => $include],
            'mailing_type' => 'standalone',
            'template_type' => 'traditional',
            'domain_id' => 1,
            'header_id' => Civi::service('nbrBackbone')->getMailingHeaderId(),
            'footer_id' => Civi::service('nbrBackbone')->getMailingFooterId(),
            'reply_id' => Civi::service('nbrBackbone')->getAutoResponderId(),
            'unsubscribe_id' => Civi::service('nbrBackbone')->getUnsubscribeId(),
            'resubscribe_id' => Civi::service('nbrBackbone')->getResubscribeId(),
            'template_options' => '{"nonce":"1"}',
        ];
        $fromFormParams = ['subject', 'from_name', 'from_email'];
        foreach ($fromFormParams as $fromFormParam) {
            if (isset($formValues[$fromFormParam]) && !empty($formValues[$fromFormParam])) {
                $mailingParams[$fromFormParam] = $formValues[$fromFormParam];
            }
        }
        return $mailingParams;
    }
    /**
     * Method to create the mailing for the bulk invite
     *
     * @param $groupId
     */
    private function createMailing($groupId) {
//        $mailingParams = $this->createMailingParams($groupId, $this->_submitValues, 'mailing stuff');
        $mailingParams = $this->createMailingParams($groupId, $this->_submitValues, 'mailing stuff');
        try {
            $mailing = civicrm_api3('Mailing', 'create', $mailingParams);
//             insert record to link mailing id and group id
//            $this->createNbrMailing($groupId, $mailing['id']);
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
                'nbr_mailing_type' => "msp",
                'mailing_id' => $mailingId,
                'group_id' => $groupId,
                'study_id' => $this->_studyId,
            ]);
            $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId);
            CRM_Core_Session::setStatus("Draft study bulk mailing to " . $studyNumber . " successfully created.", "Draft Study Bulk Mailing created", "success");
        }
        catch (CiviCRM_API3_Exception $ex) {
            Civi::log()->error('Could not create a record linking the study bulk mailing with ID ' . $mailingId .
                ' and group with ID ' . $groupId. '. Mailing has been successfully scheduled but the temporary group will not be deleted nor will add activity to case. Error message from API NbrMailing create: ' . $ex->getMessage());
            CRM_Core_Session::setStatus("Mailing scheduled but filing on case not possible, please contact IT support.", "Can not execute post study bulk mail process", "error");
        }
    }

}


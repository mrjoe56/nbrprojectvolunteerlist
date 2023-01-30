<?php
use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

class CRM_Nbrprojectvolunteerlist_Page_DisplayActivities extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Display Activities'));
    $contactId = (int) CRM_Utils_Request::retrieveValue('cid', 'Integer');
    $caseId = (int) CRM_Utils_Request::retrieveValue('caseid', 'Integer');

    $this->assign('contactId', $contactId);
    $this->assign('caseId', $caseId);
    $this->assign("activities", $this->getActivityData($contactId,$caseId));
    parent::run();
  }


  public function getActivityData($contactId,$caseId){

    $caseActivities=[];
    $alterParams= [1=>[$caseId,"Integer"]];

    // Select all case activities where the case matches
    $query= "SELECT * from civicrm_case_activity AS ca
    JOIN civicrm_activity AS act ON act.id=ca.activity_id WHERE ca.case_id=%1 ORDER BY act.activity_date_time DESC LIMIT 5";
    $alterSQL = CRM_Core_DAO::composeQuery($query, $alterParams);
    $dao=  CRM_Core_DAO::executeQuery($alterSQL);


    while ($dao->fetch()) {
      $caseActivities[] = $this->assembleActivityRow($dao);
    }
    return $caseActivities;
  }


  //Append activity array
  private function assembleActivityRow($caseActivity) {
    $activityTemplate = [];
    $activityTemplate['id'] = $caseActivity->id;
    $activityTemplate['activity_subject']= $caseActivity->subject;
    $activityTemplate['activity_notes']= $caseActivity->details;
    $activityTemplate['activity_date']= $caseActivity->activity_date_time;
    $activityTemplate['activity_type']= CRM_Nihrbackbone_Utils::getOptionValueLabel($caseActivity->activity_type_id, 'activity_type');
    $activityTemplate['activity_status']= CRM_Nihrbackbone_Utils::getOptionValueLabel($caseActivity->status_id, 'activity_status');


    return $activityTemplate;
  }



}

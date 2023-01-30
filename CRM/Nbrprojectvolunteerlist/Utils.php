<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class for util methods
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 Nov 2019
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_Utils {
  /**
   * Method to finish building a query with contactIds as elements and run it
   *
   * @param array $contactIds
   * @param int $index
   * @param array $queryParams
   * @return array $elements
   */
  public static function processContactQueryElements($contactIds, $index, &$queryParams) {
    $elements = [];
    foreach ($contactIds as $contactId) {
      $index++;
      $queryParams[$index] = [(int) $contactId, 'Integer'];
      $elements[] = "%" . $index;
    }
    return $elements;
  }

  /**
   * Method to add contact ids clause (contact_id IN (....)) to query
   *
   * @param $i
   * @param $contactIds
   * @param $query
   * @param $queryParams
   */
  public static function addContactIdsToQuery($i, $contactIds, &$query, &$queryParams) {
    $elements = [];
    foreach ($contactIds as $contactId) {
      $i++;
      $queryParams[$i] = [(int) $contactId, 'Integer'];
      $elements[] = "%" . $i;
    }
    $query .= implode("," , $elements) . ")";
  }

  /**
   * Method to get the qfkey setting name for the logged in user
   * @return string
   */
  public static function getQfKeySettingName() {
    return "nbr_cs_volunteerlist_qfkey_" . CRM_Core_Session::getLoggedInContactID();
  }

  /**
   * Method to get all non-workflow active message templates
   *
   * @return array
   */
  public static function getTemplateList() {
    $templates = [];
    try {
      $result = civicrm_api3('MessageTemplate', 'get', [
        'return' => ["id", "msg_title"],
        'is_active' => 1,
        'options' => ['limit' => 0, 'sort' => 'msg_title ASC'],
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
   * Method to create the params for the temporary group used by Invite by Bulk
   *
   * @param $studyId
   * @return array
   */
  public static function createBulkGroupParams($studyId) {
    $now = new DateTime();
    $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($studyId);
    return [
      'name' => "Nbr_BulkMailing_" . $now->format('Ymdhis'),
      'title' => "Temp. Study Bulk Mailing " . $studyNumber . " group on " . $now->format('Y-m-d H:i:s'),
      'description' => "This group is a temporary one used for bulk mailing study volunteers - do not update or use, will be removed automatically when mailing is completed.",
      'is_active' => 1,
      'visibility' => "User and User Admin Only",
      'group_type' => "Mailing List",
      'is_reserved' => 1,
      'created_id' => CRM_Core_Session::getLoggedInContactID()
    ];
  }

  /**
   * Method to create the params for the temporary group used by Invite by Bulk
   *
   * @param $studyId
   * @return array
   */
  public static function createInviteBulkGroupParams($studyId) {
    $now = new DateTime();
    $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($studyId);
    return [
      'name' => "Nbr_Invite_Bulk_" . $now->format('Ymdhis'),
      'title' => "Temp. Invite Bulk Mailing " . $studyNumber . " group on " . $now->format('Y-m-d H:i:s'),
      'description' => "This group is a temporary one used for inviting volunteers by Bulk Email - do not update or use, will be removed automatically when mailing is completed.",
      'is_active' => 1,
      'visibility' => "User and User Admin Only",
      'group_type' => "Mailing List",
      'is_reserved' => 1,
      'created_id' => CRM_Core_Session::getLoggedInContactID()
    ];
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
  public static function createMailingParams($studyId, $groupId, $formValues, $type = "") {
    if (empty($groupId) || empty($studyId) || empty($formValues)) {
      return FALSE;
    }
    $include = [$groupId];
    $mailingParams = [
      'name' => 'Bulk Invite study ' . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($studyId) . ' (created ' . date('d-m-Y') . ")",
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
    if ($type == 'msp') {
      $mailingParams['name'] = 'Study Bulk Mailing ' . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($studyId) . ' (created ' . date('d-m-Y') . ")";
    }
    $fromFormParams = ['subject', 'from_name', 'from_email'];
    foreach ($fromFormParams as $fromFormParam) {
      if (isset($formValues[$fromFormParam]) && !empty($formValues[$fromFormParam])) {
        $mailingParams[$fromFormParam] = $formValues[$fromFormParam];
      }
    }
    return $mailingParams;
  }

  /**
   * Method to get the invited data for a study
   *
   * @param $studyId
   * @param $contactIds
   * @return CRM_Core_DAO|DB_Error|object
   */
  public static function getInvitedData($studyId, $contactIds) {
    $studyParticipantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligiblesColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $participantTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $query = "
        SELECT vol.id AS contact_id, vol.display_name, cvnpd." . $studyParticipantColumn
      . " AS study_participant_id, cvnpd." . $eligiblesColumn. " AS eligible_status_id,
        ce.email, cvnpd." . $studyStatusColumn . " AS study_participation_status, cg.id as guardian_id,
        cg.display_name AS guardian_name, ge.email AS guardian_email
        FROM " . $participantTable . " AS cvnpd
        JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
        JOIN civicrm_case AS cas ON ccc.case_id = cas.id
        JOIN civicrm_contact AS vol ON ccc.contact_id = vol.id
        LEFT JOIN civicrm_email AS ce ON vol.id = ce.contact_id AND ce.is_primary = %1 AND ce.on_hold = %3
        LEFT JOIN civicrm_relationship AS cr ON vol.id = cr.contact_id_a AND cr.relationship_type_id = %4 AND cr.is_active = %1
          AND (cr.end_date >= CURDATE() OR cr.end_date IS NULL)
        LEFT JOIN civicrm_contact AS cg ON cr.contact_id_b = cg.id
        LEFT JOIN civicrm_email AS ge ON cg.id = ge.contact_id AND ge.is_primary = %1
        WHERE cvnpd." . $studyColumn . " = %2 AND cas.is_deleted = %3 AND vol.id IN (";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [(int) $studyId, "Integer"],
      3 => [0, "Integer"],
      4 => [Civi::service('nbrGuardian')->getGuardianRelationshipTypeId(), "Integer"],
    ];
    $i = 4;
    CRM_Nbrprojectvolunteerlist_Utils::addContactIdsToQuery($i, $contactIds, $query, $queryParams);
    return CRM_Core_DAO::executeQuery($query, $queryParams);
  }

  /**
   * Method to check email or guardian email validity
   *
   * @param $dao
   * @return false|string
   */
  public static function checkEmailValidity($dao) {
    // if contact has guardian guardian email has to be valid, can not be empty and has to allow email
    if (isset($dao->guardian_id) && !empty($dao->guardian_id)) {
      if (empty($dao->guardian_email)) {
        return "Volunteer has active guardian (" . $dao->guardian_name . ") without email address";
      }
      if (!filter_var($dao->guardian_email, FILTER_VALIDATE_EMAIL)) {
        return "Volunteer has active guardian (" . $dao->guardian_name . ") but email address of guardian is invalid";
      }
      if (!CRM_Nihrbackbone_NihrVolunteer::allowsEmail($dao->guardian_id)) {
        return "Volunteer has active guardian (" . $dao->guardian_name . ") but guardian does not want to be emailed";
      }
      if (CRM_Nihrbackbone_NihrVolunteer::isDeceased($dao->guardian_id)) {
        return "Volunteer has active guardian (" . $dao->guardian_name . ") but guardian is deceased";
      }
    }
    else {
      if (empty($dao->email)) {
        return "Volunteer has no email address";
      }
      if (!filter_var($dao->email, FILTER_VALIDATE_EMAIL)) {
        return "Volunteer email address is invalid";
      }
      if (!CRM_Nihrbackbone_NihrVolunteer::allowsEmail($dao->contact_id)) {
        return "Volunteer does not want to be emailed";
      }
      if (CRM_Nihrbackbone_NihrVolunteer::isDeceased($dao->contact_id)) {
        return "Volunteer is deceased";
      }
    }
    return FALSE;
  }

  /**
   * Method to check address validity
   *
   * @param $contact_id
   * @return false|string
   */
  public static function checkAddressValidity($contact_id) {
    try {
      civicrm_api3('Address', 'getsingle', [
        'contact_id' => $contact_id,
        'is_primary' => '1'
      ]);
    } catch (\CiviCRM_API3_Exception $API3_Exception) {
      return "Volunteer has no postal address";
    }
    if (!CRM_Nihrbackbone_NihrVolunteer::allowsPostalMail($contact_id)) {
      return "Volunteer does not want to receive postal mail";
    }
    if (CRM_Nihrbackbone_NihrVolunteer::isDeceased($contact_id)) {
      return "Volunteer is deceased";
    }
    return FALSE;
  }

  /**
   * Method to find the relevant case ids for tasks change study status and add follow up activity
   *
   * @param int $studyId
   * @param string|NULL $query
   * @param array $queryParams
   */
  public static function getRelevantCaseIdsQuery(int $studyId, ?string &$query, array &$queryParams) {
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $query = "SELECT ccc.case_id, cvnpd. " . $studyStatusColumn . " AS study_status_id, ccc.contact_id
        FROM " . $participationTable. " AS cvnpd
            LEFT JOIN civicrm_case_contact AS ccc ON cvnpd.entity_id = ccc.case_id
            LEFT JOIN civicrm_case AS cc ON ccc.case_id = cc.id
        WHERE cvnpd." . $studyColumn . " = %1 AND cc.is_deleted = %2";
    $queryParams = [
      1 => [$studyId, "Integer"],
      2 => [0, "Integer"]
    ];
  }


}


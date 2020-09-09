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
   * Method to get the filter setting name for the logged in user
   * @return string
   */
  public static function getFilterSettingName() {
    return "nbr_cs_volunteerlist_filters_" . CRM_Core_Session::getLoggedInContactID();
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
   * Method to create the params for the temporary group used by Invite by Bulk
   *
   * @return array
   */
  public static function createInviteBulkGroupParams() {
    $now = new DateTime();
    return [
      'name' => "Nbr_Invite_Bulk_" . $now->format('Ymdhis'),
      'title' => "Temporary Invite Bulk Mailing group, do not use!",
      'description' => "This group is a temporary one used for inviting volunteers by Bulk Email - do not update or use, will be removed automatically when mailing is completed.",
      'is_active' => 1,
      'visibility' => "User and User Admin Only",
      'group_type' => "Mailing List",
      'is_hidden' => 1,
      'is_reserved' => 1,
      'created_id' => CRM_Core_Session::getLoggedInContactID()
    ];
  }

}


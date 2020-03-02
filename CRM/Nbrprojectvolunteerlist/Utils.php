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
   * Method to set the update params
   *
   * @param array $caseIds
   * @param string $statusColumn
   * @param string $statusId
   * @param array $updateParams
   * @param string $update
   * @return array
   */
  public static function setUpdateParams($caseIds, $statusColumn, $statusId, &$updateParams, &$update) {
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $update = "UPDATE " . $participationTable . " SET " . $statusColumn . " = %1 WHERE entity_id IN (";
    $updateParams = [1 => [$statusId, "String"]];
    $i = 1;
    $elements = [];
    foreach ($caseIds as $caseId => $caseStatus) {
      $i++;
      $elements[$i] = "%" . $i;
      $updateParams[$i] = [(int) $caseId, "Integer"];
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
}


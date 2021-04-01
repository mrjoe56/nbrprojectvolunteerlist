<?php
use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Nbrprojectvolunteerlist_Upgrader extends CRM_Nbrprojectvolunteerlist_Upgrader_Base {

  /**
   * Upgrade 1000 remove redundant setting
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1000() {
    $this->ctx->log->info('Applying update 1000');
    $query = "DELETE FROM civicrm_setting WHERE name IN(%1, %2)";
    CRM_Core_DAO::executeQuery($query, [
      1 => ["nbr_cs_volunteerlist_qfKey", "String"],
      2 => ["nbr_cs_project_volunteer_qfKey", "String"],
      ]);
    return TRUE;
  }

  /**
   * Upgrade 1010 remove unwanted max invites eligibility statuses
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_1010() {
    $this->ctx->log->info('Applying update 1010 - remove unwanted max invites eligibility statuses');
    $oldOptionValues = [];
    $remainingOptionValue = NULL;
    $ovQuery = "SELECT id, name, value FROM civicrm_option_value WHERE option_group_id = %1 AND name IN(%2, %3, %4)";
    $ovQueryParams = [
      1 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId(), "Integer"],
      2 => ["nihr_max_invites", "String"],
      3 => ["nihr_reached_max", "String"],
      4 => ["nihr_maximum_reached", "String"]
    ];
    $optionValue = CRM_Core_DAO::executeQuery($ovQuery, $ovQueryParams);
    while ($optionValue->fetch()) {
      if ($optionValue->name == "nihr_max_invites" && !$remainingOptionValue) {
        $remainingOptionValue = CRM_Core_DAO::VALUE_SEPARATOR . $optionValue->value . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      else {
        $oldOptionValues[] = CRM_Core_DAO::VALUE_SEPARATOR . $optionValue->value . CRM_Core_DAO::VALUE_SEPARATOR;
        $delete = "DELETE FROM civicrm_option_value WHERE id = %1";
        $deleteParams = [1 => [$optionValue->id, "Integer"]];
        CRM_Core_DAO::executeQuery($delete, $deleteParams);
      }
    }
    foreach ($oldOptionValues as $oldOptionValue) {
      $pdQuery = "SELECT id, nvpd_eligible_status_id FROM civicrm_value_nbr_participation_data WHERE nvpd_eligible_status_id LIKE %1";
      $pdQueryParams = [1 => ["%" . $oldOptionValue . "%", "String"]];
      $partData = CRM_Core_DAO::executeQuery($pdQuery, $pdQueryParams);
      while ($partData->fetch()) {
        $eligible = str_replace($oldOptionValue, $remainingOptionValue, $partData->nvpd_eligible_status_id);
        $update = "UPDATE civicrm_value_nbr_participation_data SET nvpd_eligible_status_id = %1 WHERE id = %2";
        $updateParams = [
          1 => [$eligible, "String"],
          2 => [$partData->id, "Integer"],
        ];
        CRM_Core_DAO::executeQuery($update, $updateParams);
      }
    }
    return TRUE;
  }


  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}

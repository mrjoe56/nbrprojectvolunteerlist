<?php

use CRM_Nbrprojectvolunteerlist_ExtensionUtil as E;

/**
 * Class for MSP specific methods
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 14 Sep 2021
 * @license AGPL-3.0
 */
class CRM_Nbrprojectvolunteerlist_NbrCustomSearch {
  private $_mspClass;
  public function __construct() {
    $this->_mspClass = "CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList";
  }
  public function mspBuildForm(&$form) {
    $className = $form->getVar('_customSearchClass');
    if ($className && $className == $this->_mspClass) {
      if (isset($form->_formValues['study_id']) && !empty($form->_formValues['study_id'])) {
        $studyId = (int) $form->_formValues['study_id'];
        if (CRM_Nihrbackbone_NbrStudy::hasNoActionStatus($studyId)) {
          CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nbrprojectvolunteerlist/nbr_readonly_msp_actions.tpl',]);
        }
      }
    }
  }

}


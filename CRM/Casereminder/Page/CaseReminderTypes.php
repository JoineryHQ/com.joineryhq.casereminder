<?php
use CRM_Casereminder_ExtensionUtil as E;

class CRM_Casereminder_Page_CaseReminderTypes extends CRM_Core_Page_Basic {

  /**
   * @inheritDoc
   * @var bool
   */
  public $useLivePageJS = TRUE;

  /**
   * @inheritDoc
   * @var string
   */
  public static $_links = NULL;

  /**
   * @inheritDoc
   * @var string
   */
  public function getBAOName() {
    return 'CRM_Casereminder_BAO_CaseReminderType';
  }

  /**
   * @inheritDoc
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        (CRM_Core_Action::UPDATE) => array(
          'name' => E::ts('Edit'),
          'url' => 'civicrm/admin/casereminder/types/',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => E::ts('Edit Case Reminder Type'),
        ),
        (CRM_Core_Action::DELETE) => array(
          'name' => E::ts('Delete'),
          'url' => 'civicrm/admin/casereminder/types/',
          'qs' => 'action=delete&id=%%id%%',
          'title' => E::ts('Delete Case Reminder Type'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * @inheritDoc
   */
  public function run() {
    return parent::run();
  }

  /**
   * @inheritDoc
   */
  public function browse() {
//    $jsVars = [];
//    $userCid = CRM_Core_Session::singleton()->getLoggedInContactID();

    parent::browse();

//    $rows = $this->get_template_vars('rows');
//    foreach ($rows as &$row) {
//      $row['entity_type'] = CRM_Jentitylink_Util::arrayExplodePaddedTrim($row['entity_type']);
//    }
//    ksort($rows);
//    $this->assign('rows', $rows);
//    $this->assign('entity_type_options', CRM_Jentitylink_Util::getEntityTypeOptions());
//    $this->assign('entity_name_options', CRM_Jentitylink_Linkbuilder::getSupportedEntityNames());

//    CRM_Core_Resources::singleton()->addVars('jentitylink', $jsVars);
//    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.jentitylink', 'js/CRM_Jentitylink_Page_Links.js');
//    CRM_Core_Resources::singleton()->addStyleFile('com.joineryhq.jentitylink', 'css/CRM_Jentitylink_Page_Links.css');

  }

  /**
   * @inheritDoc
   */
  public function editForm() {
    return 'CRM_Jentitylink_Form_Link';
  }

  /**
   * @inheritDoc
   */
  public function editName() {
    return E::ts('Entity Navigation Links');
  }

  /**
   * @inheritDoc
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/casereminder/types';
  }

}

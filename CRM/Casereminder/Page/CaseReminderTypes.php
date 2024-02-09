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
    parent::browse();

    $rows = $this->get_template_vars('rows');
    foreach ($rows as &$row) {
      $row['case_status_id'] = CRM_Utils_Array::explodePadded($row['case_status_id']);
      $row['recipient_relationship_type_id'] = CRM_Utils_Array::explodePadded($row['recipient_relationship_type_id']);
    }
    ksort($rows);
    $this->assign('rows', $rows);
    $this->assign('case_type_options', CRM_Case_BAO_Case::buildOptions('case_type_id'));
    $this->assign('case_status_options', CRM_Case_BAO_Case::buildOptions('case_status_id'));
    $this->assign('msg_template_options', CRM_Core_BAO_MessageTemplate::getMessageTemplates(FALSE));
    $this->assign('recipient_options', array_flip(array_merge(
      array(E::ts('Case Contact') => -1),
      array_flip(CRM_Contact_BAO_Relationship::buildOptions('relationship_type_id'))
    )));
  }

  /**
   * @inheritDoc
   */
  public function editForm() {
    return 'CRM_Casereminder_Form_CaseReminderType';
  }

  /**
   * @inheritDoc
   */
  public function editName() {
    return E::ts('Case Reminder Type');
  }

  /**
   * @inheritDoc
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/casereminder/types';
  }

}

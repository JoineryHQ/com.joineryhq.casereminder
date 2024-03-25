<?php

use CRM_Casereminder_ExtensionUtil as E;
use Civi\Token\TokenProcessor;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Casereminder_Form_CaseReminderType extends CRM_Admin_Form {

  public static function getDowOptions() {
    return [
      'sunday' => E::ts('Sunday'),
      'monday' => E::ts('Monday'),
      'tuesday' => E::ts('Tuesday'),
      'wednesday' => E::ts('Wednesday'),
      'thursday' => E::ts('Thursday'),
      'friday' => E::ts('Friday'),
      'saturday' => E::ts('Saturday'),
    ];
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'CaseReminderType';
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::setTitle('Delete Case Reminder Type');
    }
    elseif ($this->_action & CRM_Core_Action::UPDATE) {
      CRM_Utils_System::setTitle('Edit Case Reminder Type');
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      CRM_Utils_System::setTitle('Create Case Reminder Type');
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $descriptions['delete_warning'] = E::ts('Are you sure you want to delete this Case Reminder Type?');
    }
    else {
      $descriptions = [
        'case_type_id' => NULL,
        'case_status_id' => E::ts('If any statuses are selected, reminders will only be sent for cases in one of the selected statuses. Select nothing to send reminders regardless of case status.'),
        'msg_template_id' => NULL,
        'recipient_relationship_type_id' => NULL,
        'from_email_address' => NULL,
        'subject' => E::ts('This subject will be used in place of any subject line configured for the selected Message Template.'),
        'dow' => NULL,
        'max_iterations' => E::ts('Maximum number of times to send this reminder on any given case. If this is blank (or 0), no such limitation will be enforced.'),
        'is_active' => NULL,
      ];

      $caseStatusValuesByTypeId = $this->getCaseStatusValuesByTypeId();
      $caseStatusOptions = $this->getCaseStatusOptions();
      $caseRoleValuesByTypeId = $this->getCaseRoleValuesByTypeId();
      $recipientOptions = $this->getRecipientOptions();

      $caseTypeOptions = CRM_Case_BAO_Case::buildOptions('case_type_id');
      $this->add(
        // field type
        'select',
        // field name
        'case_type_id',
        // field label
        E::ts('Case Type'),
        // list of options
        $caseTypeOptions,
        // is required
        TRUE,
        // attributes
        ['class' => 'crm-select2', 'placeholder' => E::ts('- select -'), 'style' => 'width: 30rem;']
      );

      $this->addCheckBox(
        'case_status_id',
        ts('Case Status'),
        $caseStatusOptions,
        NULL,
        NULL,
        NULL,
        NULL,
        ''
      );

      $msgTemplateOptions = CRM_Core_BAO_MessageTemplate::getMessageTemplates(FALSE);
      $this->add(
        // field type
        'select',
        // field name
        'msg_template_id',
        // field label
        E::ts('Message Template'),
        // list of options
        $msgTemplateOptions,
        // is required
        TRUE,
        // attributes
        ['class' => 'crm-select2', 'placeholder' => E::ts('- select -'), 'style' => 'width: 30rem;']
      );

      $this->addCheckBox('recipient_relationship_type_id', ts('Recipients'),
        $recipientOptions,
        NULL,
        NULL,
        TRUE,
        NULL,
        ''
      );

      $fromOptions = CRM_Core_BAO_Email::domainEmails();
      $this->add(
        // field type
        'select',
        // field name
        'from_email_address',
        // field label
        E::ts('From'),
        // list of options
        $fromOptions,
        // is required
        TRUE,
        // attributes
        ['class' => 'crm-select2', 'placeholder' => E::ts('- select -'), 'style' => 'width: 30rem;']
      );

      $this->add(
        // field type
        'text',
        // field name
        'subject',
        // field label
        E::ts('Subject'),
        // attributes
        ['style' => 'width: calc(30rem - 10em); float: left; margin-right: 1em;'],
        // is required
        TRUE
      );

      $dowOptions = self::getDowOptions();

      $this->add(
        // field type
        'select',
        // field name
        'dow',
        // field label
        E::ts('Send on'),
        // list of options
        $dowOptions,
        // is required
        TRUE,
        // attributes
        ['class' => 'crm-select2', 'placeholder' => E::ts('- select -'), 'style' => 'width: 30rem;']
      );

      $this->add('number', 'max_iterations', E::ts('Maximum iterations per case'), ['min' => 0]);
      $this->addRule('max_iterations', E::ts('Value should be a positive number'), 'positiveInteger');

      $this->add('checkbox', 'is_active', ts('Enabled?'));
    }

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->assign('descriptions', $descriptions);
    $this->assign('id', $this->_id);

    // Get metadata for on-page behaviors.
    $jsVars = [
      'caseStatusValuesByTypeId' => $caseStatusValuesByTypeId,
      'caseRoleValuesByTypeId' => $caseRoleValuesByTypeId,
    ];

    CRM_Core_Resources::singleton()->addvars('casereminder', $jsVars);
    // Add css files
    CRM_Core_Resources::singleton()->addStyleFile('casereminder', 'css/CRM_Casereminder_Form_CaseReminderType.css');
    // Add js file
    CRM_Core_Resources::singleton()->addScriptFile('casereminder', 'js/CRM_Casereminder_Form_CaseReminderType.js');

    CRM_Mailing_BAO_Mailing::commonCompose($this);
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    if ($this->_id && (!($this->_action & CRM_Core_Action::DELETE))) {
      $result = _casereminder_civicrmapi('CaseReminderType', 'getSingle', array(
        'id' => $this->_id,
      ));
      $defaultValues = $result;
      // Unpack multi-value packed values.
      $defaultValues['case_status_id'] = array_fill_keys($defaultValues['case_status_id'], 1);
      $defaultValues['recipient_relationship_type_id'] = array_fill_keys($defaultValues['recipient_relationship_type_id'], 1);

    }
    elseif (!$this->_id && (($this->_action & CRM_Core_Action::ADD))) {
      $fromAddresses = CRM_Core_OptionGroup::values('from_email_address');
      $defaultFromAddressKey = CRM_Core_OptionGroup::getDefaultValue('from_email_address');
      $defaultValues = [
        'from_email_address' => $fromAddresses[$defaultFromAddressKey],
        'is_active' => 1,
      ];
    }
    return $defaultValues;
  }

  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $apiParams['id'] = $this->_id;
      _casereminder_civicrmapi('CaseReminderType', 'delete', $apiParams);
      CRM_Core_Session::setStatus(E::ts('Case Reminder Type has been deleted.'), E::ts('Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $submitted = $this->exportValues();
      $apiParams = $submitted;
      $apiParams['case_status_id'] = array_keys($apiParams['case_status_id']);
      $apiParams['recipient_relationship_type_id'] = array_keys($apiParams['recipient_relationship_type_id']);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $apiParams['id'] = $this->_id;
        // Ensure is_active has a value (because it's a checkbox, it will be undefined
        // (and thus not saved) if it's un-checked.
        if (!isset($apiParams['is_active'])) {
          $apiParams['is_active'] = 0;
        }
      }

      $caseReminderType = _casereminder_civicrmapi('CaseReminderType', 'create', $apiParams);

      CRM_Core_Session::setStatus(E::ts('Case Reminder Type has been saved.'), E::ts('Saved'), 'success');
    }
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  private function getCaseTypeDefinitions() {
    static $ret;
    if (!isset($ret)) {
      $ret = [];
      $caseTypeGet = _casereminder_civicrmapi('caseType', 'get', [
        'sequential' => 1,
        'return' => ["definition"],
        'is_active' => TRUE,
        'options' => ['limit' => 0],
      ]);
      foreach ($caseTypeGet['values'] as $value) {
        $ret[$value['id']] = $value['definition'];
      }
    }
    return $ret;
  }

  private function getCaseStatusOptions() {
    $caseStatusValuesByTypeId = $this->getCaseStatusValuesByTypeId();
    $buildOptions = $this->filterOptionsByCaseOptions(CRM_Case_BAO_Case::buildOptions('case_status_id'), $caseStatusValuesByTypeId);
    return array_flip($buildOptions);
  }

  private function getCaseStatusValuesByTypeId() {
    static $caseStatusValuesByTypeId;
    if (!isset($caseStatusValuesByTypeId)) {
      $caseStatusValuesByTypeId = [];

      $caseStatusGet = $result = _casereminder_civicrmapi('OptionValue', 'get', [
        'sequential' => 1,
        'option_group_id' => "case_status",
        'is_active' => TRUE,
      ]);
      $caseStatusesByName = CRM_Utils_Array::rekey($caseStatusGet['values'], 'name');

      // A case type may have no specific statuses configured, which means it will
      // use the default options: all available statuses.
      $defaultStatuses = array_values(CRM_Utils_Array::collect('value', $caseStatusesByName));

      $caseTypeDefinitions = $this->getCaseTypeDefinitions();
      foreach ($caseTypeDefinitions as $caseTypeId => $caseTypeDefinition) {
        $caseStatusValuesByTypeId[$caseTypeId] = [];
        if (isset($caseTypeDefinition['statuses']) && is_array($caseTypeDefinition['statuses'])) {
          // If this case type has statuses defined, use those.
          foreach ($caseTypeDefinition['statuses'] as $statusName) {
            $caseStatusValuesByTypeId[$caseTypeId][] = $caseStatusesByName[$statusName]['value'];
          }
        }
        else {
          // Otherwise, use the default options.
          $caseStatusValuesByTypeId[$caseTypeId] = $defaultStatuses;
        }
      }
    }
    return $caseStatusValuesByTypeId;
  }

  private function getRecipientOptions() {
    $caseRoleValuesByTypeId = $this->getCaseRoleValuesByTypeId();
    $buildOptions = [
      '-1' => E::ts('Case client'),
    ];
    $buildOptions += $this->filterOptionsByCaseOptions(CRM_Contact_BAO_Relationship::buildOptions('relationship_type_id'), $caseRoleValuesByTypeId);
    return array_flip($buildOptions);
  }

  private function getCaseRoleValuesByTypeId() {
    static $caseRoleValuesByTypeId;
    if (!isset($caseRoleValuesByTypeId)) {
      $caseRoleValuesByTypeId = [];

      $relationshipTypeGet = $result = _casereminder_civicrmapi('RelationshipType', 'get', [
        'sequential' => 1,
        'option_group_id' => "case_status",
        'is_active' => TRUE,
      ]);
      $relationshipTypesByName = CRM_Utils_Array::rekey($relationshipTypeGet['values'], 'name_b_a');

      $caseTypeDefinitions = $this->getCaseTypeDefinitions();
      foreach ($caseTypeDefinitions as $caseTypeId => $caseTypeDefinition) {
        // Start with -1 ("Case client"), which is a "fake" role that's valid for all case types.
        $caseRoleValuesByTypeId[$caseTypeId] = ['-1'];
        foreach ($caseTypeDefinition['caseRoles'] as $caseRole) {
          $roleName = $caseRole['name'];
          $caseRoleValuesByTypeId[$caseTypeId][] = $relationshipTypesByName[$roleName]['id'];
        }
      }
    }
    return $caseRoleValuesByTypeId;
  }

  private function filterOptionsByCaseOptions($options, $caseStatusValuesByTypeId) {
    $caseValues = [];
    foreach ($caseStatusValuesByTypeId as $values) {
      $caseValues += array_intersect_key($options, array_flip($values));
    }
    return $caseValues;
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => $this->getTokenSchema()]);
    return $tokenProcessor->listTokens();
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  protected function getTokenSchema(): array {
    return ['contactId'];
  }

}

CRM.$(function ($) {
  var casereminderCaseTypeChange = function casereminderCaseTypeChange() {
    var statusValue;
    var inputId;
    var caseTypeValue = $('#case_type_id').val();
    $('.crm-section.casereminder-element-case_status_id .content input, .crm-section.casereminder-element-case_status_id .content label').hide();
    for (var i in CRM.vars.casereminder.caseStatusValuesByTypeId[caseTypeValue]) {
      statusValue = CRM.vars.casereminder.caseStatusValuesByTypeId[caseTypeValue][i];
      inputId = 'case_status_id_' + statusValue;
      $('.crm-section.casereminder-element-case_status_id .content input#' + inputId).show();
      $('.crm-section.casereminder-element-case_status_id .content label[for="' + inputId +'"]').show();
    }

    $('.crm-section.casereminder-element-recipient_relationship_type_id .content input, .crm-section.casereminder-element-recipient_relationship_type_id .content label').hide();
    for (var n in CRM.vars.casereminder.caseRoleValuesByTypeId[caseTypeValue]) {
      statusValue = CRM.vars.casereminder.caseRoleValuesByTypeId[caseTypeValue][n];
      inputId = 'recipient_relationship_type_id_' + statusValue;
      $('.crm-section.casereminder-element-recipient_relationship_type_id .content input#' + inputId).show();
      $('.crm-section.casereminder-element-recipient_relationship_type_id .content label[for="' + inputId +'"]').show();
    }
  };

  $('#case_type_id').change(casereminderCaseTypeChange);

  casereminderCaseTypeChange();
});
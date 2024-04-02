{if $action eq 1 || $action eq 2 || $action eq 4 || $action eq 8}
  {include file="CRM/Casereminder/Form/CaseReminderType.tpl"}
{else}
  <div class="help">
    <p>{ts}Manage Case Reminder Types.{/ts}</p>
  </div>

  <div class="crm-content-block crm-block">
    {if $rows}
      {if $rows|@count > 5}
        <div class="action-link">
          {crmButton p="civicrm/admin/casereminder/type" q="action=add&reset=1" icon="plus-circle"}{ts}Add Case Reminder Type{/ts}{/crmButton}
        </div>
      {/if}

      <div id="ltype">
        {strip}
          {* handle enable/disable actions*}
          {include file="CRM/common/enableDisableApi.tpl"}
          {include file="CRM/common/jsortable.tpl"}
          <table id="options" class="display">
            <thead>
              <tr>
                <th id="sortable">{ts}ID{/ts}</th>
                <th id="sortable">{ts}Case type{/ts}</th>
                <th id="sortable">{ts}Case status{/ts}</th>
                <th id="sortable">{ts}Template{/ts}</th>
                <th id="sortable">{ts}Recipients{/ts}</th>
                <th id="sortable">{ts}Subject{/ts}</th>
                <th id="sortable">{ts}Send On{/ts}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$rows item=row key=linkId}
                <tr id="caseremindertype-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if $row.is_active == 0} disabled{/if} ">
                  <td class="crm-caseremindertype-id" data-field="id">{$row.id}</td>
                  <td class="crm-caseremindertype-case-type-di" data-field="case_type_id">{$case_type_options[$row.case_type_id]}</td>
                  <td class="crm-caseremindertype-case-status-id" data-field="case_status_id">
                    {if empty($row.case_status_id)}
                      (Any)
                    {else}
                      {foreach from=$row.case_status_id item=case_status_id}
                        {$case_status_options.$case_status_id}<br>
                      {/foreach}
                    {/if}
                  </td>
                  <td class="crm-caseremindertype-msg-template-id" data-field="msg_template_id">
                    {$msg_template_options[$row.msg_template_id]}
                  </td>
                  <td class="crm-caseremindertype-recipient-relationship-type-id" data-field="recipient_relationship_type_id">
                    {foreach from=$row.recipient_relationship_type_id item=recipient_relationship_type_id}
                      {$recipient_options.$recipient_relationship_type_id}<br>
                    {/foreach}
                  </td>
                  <td class="crm-caseremindertype-subject" data-field="subject">{$row.subject}</td>
                  <td class="crm-caseremindertype-dow" data-field="dow">{$row.dow}</td>
                  <td>{$row.extraAction|replace:'xx':$row.id}{$row.action|replace:'xx':$row.id}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        {/strip}
      </div>
    {else}
      <div class="messages status no-popup">
        <i class="crm-i fa-info-circle" aria-hidden="true"></i>
        {ts}There are currently no Case Reminder Types.{/ts}
      </div>
    {/if}
    <div class="action-link">
      {crmButton p="civicrm/admin/casereminder/type" q="action=add&reset=1" icon="plus-circle"}{ts}Add Case Reminder Type{/ts}{/crmButton}
    </div>
  </div>


{/if}
{* HEADER *}
<div class="crm-content-block crm-block">
<div class="crm-submit-buttons"></div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}
{if $id}
  <div class="crm-section">
    <div class="label">{ts}ID{/ts}</div>
    <div class="content">{$id}</div>
    <div class="clear"></div>
  </div>
{/if}

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}<div class="description">{$descriptions.$elementName}</div></div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="crm-section">
  <div>
    {if $descriptions.delete_warning}<br /><span id="delete_warning" class="description">{$descriptions.delete_warning}</span>{/if}
  </div>
  <div class="clear"></div>
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

</div>
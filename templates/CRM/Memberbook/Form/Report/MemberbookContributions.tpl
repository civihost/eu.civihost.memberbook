{* Use the default layout *}
{*{include file="CRM/Report/Form.tpl"} *}

{if $outputMode == 'pdf'}
  <div class="crm-block crm-content-block crm-report-layoutTable-form-block">

    <div id="report-date">{if !empty($reportDate)}{$reportDate}{/if}</div>

    {include file="CRM/Report/Form/Layout/Table.tpl"}
  </div>
{else}
  {include file="CRM/Report/Form.tpl"}
{/if}

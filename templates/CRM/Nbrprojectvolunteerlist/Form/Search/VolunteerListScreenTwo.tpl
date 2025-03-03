{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* Default template custom searches. This template is used automatically if templateFile() function not defined in
   custom search .php file. If you want a different layout, clone and customize this file and point to new file using
   templateFile() function.*}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
    <div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}collapsed{/if}">
        <div class="crm-accordion-header crm-master-accordion-header">
            {ts}Edit Search Criteria{/ts}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
            <table class="form-layout-compressed">
                {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
                {foreach from=$elements item=element}
                    <tr class="crm-contact-custom-search-form-row-{$element}">
                        <td class="label">{$form.$element.label}</td>
                        <td>{$form.$element.html}</td>
                        {if $form.$element.name == "gender_id"}
                            <td>{$form.inex_gender_id.html}</td>
                        {/if}
                        {if $form.$element.name == "ethnicity_id"}
                            <td>{$form.inex_ethnicity_id.html}</td>
                        {/if}
                        {if $form.$element.name == "recall_group"}
                            <td>{$form.inex_recall_group.html}</td>
                        {/if}
                        {if $form.$element.name == "study_status_id"}
                            <td>{$form.inex_study_status_id.html}</td>
                        {/if}
                        {if $form.$element.name == "eligibility_status_id"}
                            <td>{$form.inex_eligibility_status_id.html}</td>
                        {/if}
                        {if $form.$element.name == "tags"}
                            <td>{$form.inex_tags.html}</td>
                        {/if}
                        {if $form.$element.name == "invite_date_to"}
                            <td>{$form.inex_invite_date.html}</td>
                        {/if}

                        {if $form.$element.name == "age_to"}
                            <td>{$form.inex_age.html}</td>

                        {/if}


                        {if $form.$element.name == "activity_status_id"}
                            <td>{$form.inex_activity_status_id.html}</td>
                        {/if}
                        {if $form.$element.name == "activity_type_id"}
                            <td>{$form.inex_activity_type_id.html}</td>
                        {/if}

                    </tr>
                {/foreach}
            </table>
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
        </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
    <div class="crm-content-block">
        {if $rowsEmpty}
            {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
        {/if}

        {if $summary}
            {$summary.summary}: {$summary.total}
        {/if}

        {if $rows}
            <div class="crm-results-block">
                {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
                {* This section handles form elements for action task select and submit *}
                <div class="crm-search-tasks">
                    {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
                </div>
                {* This section displays the rows along and includes the paging controls *}
                <div class="crm-search-results">

                    {include file="CRM/common/pager.tpl" location="top"}

                    {* Include alpha pager if defined. *}
                    {if $atoZ}
                        {include file="CRM/common/pagerAToZ.tpl"}
                    {/if}

                    {strip}
                        <table class="selector row-highlight" summary="{ts}Search results listings.{/ts}">
                            <thead class="sticky">
                            <tr>
                                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                                {foreach from=$columnHeaders item=header}
                                {if $header.name != "Case ID"}
                        {* Turn off sorting on columns, as when they're sorted the add activity button doesn't select them,
                        I think because they're not part of the contact table *}
                                <th scope="col">
                                    {if $header.sort and $header.name ne "Gndr" and $header.name ne "Ethn." and $header.name ne "Loc."
                                    and $header.name ne "Status" and $header.name ne "Part. ID" and $header.name ne "Email" and $header.name ne "Tag(s)"
                                    and $header.name ne "Inv. Date" and $header.name ne "Researcher Date" and $header.name ne "Latest Visit Date"
                                    and $header.name ne "BioResource ID" and $header.name ne "Eligibility" and $header.name ne "Recall Group"
                                    and $header.name ne "Latest Activity Type" and $header.name ne "Activity Date" and $header.name ne "Notes"
                                    and $header.name ne "Activity Status" and $header.name ne "Subject"  and $header.name ne "Activity Assignee"}


                                        {assign var='key' value=$header.sort}
                                        {$sort->_response.$key.link}
                                    {else}
                                        {$header.name}
                                    {/if}
                                    {/if}
                                    {/foreach}
                                <th>&nbsp;</th>
                            </tr>
                            </thead>

                            {counter start=0 skip=1 print=false}
                            {foreach from=$rows item=row}
                                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                                    {assign var=cbName value=$row.checkbox}
                                    <td>{$form.$cbName.html}</td>
                                    {foreach from=$columnHeaders item=header}
                                        {if $header.name != 'Case ID'}
                                            {assign var=fName value=$header.sort}
                                            {if $fName eq 'sort_name'}
                                                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=custom"}">{$row.sort_name}</a></td>
                                            {else}
                                                <td>{$row.$fName}</td>
                                            {/if}
                                        {/if}
                                    {/foreach}
                                    <td><a href="{crmURL p='civicrm/contact/view/case' q="reset=1&id=`$row.case_id`&cid=`$row.contact_id`&action=view&context=case"}">Manage Case</a></td>
                                    <td><a class="action-item button" href="{crmURL p='civicrm/nbrprojectvolunteerlist/displayactivities' q="reset=1&caseid=`$row.case_id`&cid=`$row.contact_id`"}">View Activities</a></td>
                                </tr>
                            {/foreach}
                        </table>
                    {/strip}

                    {include file="CRM/common/pager.tpl" location="bottom"}

                    </p>
                    {* END Actions/Results section *}
                </div>
            </div>
        {/if}
    </div>
{/if}

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    // Clear any old selection that may be lingering in quickform
    $("input.select-row, input.select-rows", 'form.crm-search-form').attr('checked', false).closest('tr').removeClass('crm-row-selected');
    // Retrieve stored checkboxes
    var selectedIds = {/literal}{$selectedIds|@json_encode}{literal}
    if (selectedIds.length > 0) {
      $('#mark_x_' + selectedIds.join(',#mark_x_') + ',input[name=radio_ts][value=ts_sel]').trigger('click');
    }
  });
  {/literal}
</script>

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">
  {if $single eq false}
    <div class="messages status no-popup">{include file="CRM/Contact/Form/Task.tpl"}</div>
  {/if}
  {if $invalid_ids|@count gt 0}
    <div class="crm-accordion-wrapper nbr_invalids">
      <div class="crm-accordion-header">
        <h3>Warning: the volunteers below will NOT be invited!</h3>
        <p>Their status is other than selected or they are not eligible.</p>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <thead>
              <tr>
                <th>Volunteer</th>
                <th>Status</th>
                <th>Eligibility</th>
                <th>Reason not selected</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$invalid_ids key=invalid_id item=invalid}
                <tr id='invalid{$invalid_id}' class="{cycle values="odd-row,even-row"}">
                  <td>{$invalid.display_name}</td>
                  <td>{$invalid.study_status}</td>
                  <td>{$invalid.eligible_status}</td>
                  <td>{$invalid.invalid_status}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {/if}
  {if $invited_ids|@count gt 0}
    <div class="crm-accordion-wrapper nbr_invited">
      <div class="crm-accordion-header">
        <h3>The following volunteers will be invited by PDF</h3>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <thead>
              <tr>
                <th>Volunteer</th>
                <th>Status</th>
                <th>Eligibility</th>
              </tr>
          </thead>
          <tbody>
            {foreach from=$invited_ids key=invited_id item=invited}
              <tr id='invited{$invited_id}' class="{cycle values="odd-row,even-row"}">
                <td>{$invited.display_name}</td>
                <td>{$invited.study_status}</td>
                <td>{$invited.eligible_status}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    </div>
  </div>
  {else}
    <p>There are no volunteers left to invite, press Cancel</p>
  {/if}
  {if $invited_ids|@count gt 0}
    {include file="CRM/Nbrprojectvolunteerlist/Form/Task/InviteLetterCommon.tpl"}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  {/if}
</div>

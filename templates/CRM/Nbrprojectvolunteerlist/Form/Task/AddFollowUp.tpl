{* Confirm contacts and add follow up activity *}
<h2>{ts}Add Follow Up Activity{/ts}</h2>
<div class="messages status no-popup">
  <div class="crm-form-block nbr-add-followup-block">
    <div class="crm-section nbr-add-followup-assigned-section">
      <div class="label">{$form.nbr_assignee_id.label}</div>
      <div class="content">{$form.nbr_assignee_id.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section nbr-add-followup-subject-section">
      <div class="label">{$form.nbr_subject.label}</div>
      <div class="content">{$form.nbr_subject.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section nbr-add-followup-activity-datetime-section">
      <div class="label">{$form.nbr_activity_datetime.label}</div>
      <div class="content">{$form.nbr_activity_datetime.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section nbr-add-followup-details-section">
      <div class="label">{$form.nbr_details.label}</div>
      <div class="content">{$form.nbr_details.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section nbr-add-followup-status-section">
      <div class="label">{$form.nbr_status_id.label}</div>
      <div class="content">{$form.nbr_status_id.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section nbr-add-followup-priority-section">
      <div class="label">{$form.nbr_priority_id.label}</div>
      <div class="content">{$form.nbr_priority_id.html}</div>
      <div class="clear"></div>
    </div>
  </div>
  {if !empty($selected)}
    <table class="table nbr_add-followup-selected-table">
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Study/Participant ID{/ts}</th>
        <th>{ts}Eligibility{/ts}</th>
        <th>{ts}Study participant status{/ts}</th>
      </tr>
        {foreach from=$selected key=contact_id item=volunteer}
          <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
            <td>{$volunteer.display_name}</td>
            <td>{$volunteer.study_participant_id}</td>
            <td>{$volunteer.eligible_status}</td>
            <td>{$volunteer.study_status}</td>
          </tr>
        {/foreach}
    </table>
  {/if}
  <p>{$count_selected_txt}</p>
</div>
<p>
<div class="form-item">
    {$form.buttons.html}
</div>

{* Confirm contacts and change study status *}
<h2>{ts}Change Study Status{/ts}</h2>
<div class="messages status no-popup">
  <div class="crm-form-block">
    <h3>{$status_txt}</h3>
    <div class="crm-section">
      <div class="label">{$form.nbr_study_status_id.label}</div>
      <div class="content">{$form.nbr_study_status_id.html}</div>
      <div class="clear"></div>
    </div>
  </div>
  <h3>{$selected_txt}</h3>
    {if !empty($selected)}
      <table>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Current Study Status{/ts}</th>
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
  <h3>{$warning_txt}</h3>
  {if !empty($warnings)}
    <table>
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}BioResource ID{/ts}</th>
        <th>{ts}Participant ID{/ts}</th>
        <th>{ts}Study/Participant ID{/ts}</th>
        <th>{ts}Eligibility{/ts}</th>
        <th>{ts}Email{/ts}</th>
      </tr>
      {foreach from=$warnings key=contact_id item=warning}
        <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
          <td>{$warning.display_name}</td>
          <td>{$warning.study_participant_id}</td>
          <td>{$warning.eligible_status}</td>
        </tr>
      {/foreach}
    </table>
  {/if}
  <p>{$count_warning_txt}</p>
</div>
<p>
<div class="form-item">
    {$form.buttons.html}
</div>

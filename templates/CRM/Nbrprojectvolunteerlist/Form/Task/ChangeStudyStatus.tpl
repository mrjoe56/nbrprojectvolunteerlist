{* Confirm contacts and change project status *}
<h2>{ts}Change Project Status{/ts}</h2>
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
          <th>{ts}BioResource ID{/ts}</th>
          <th>{ts}Participant ID{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Current Study Status{/ts}</th>
        </tr>
          {foreach from=$selected key=contact_id item=volunteer}
            <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$volunteer.display_name}</td>
              <td>{$volunteer.bioresource_id}</td>
              <td>{$volunteer.participant_id}</td>
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

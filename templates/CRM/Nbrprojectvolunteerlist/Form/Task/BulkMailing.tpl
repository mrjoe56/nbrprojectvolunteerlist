{* Confirm contacts and select template for InviteByEmail *}
<h2>{ts}Study Bulk Mailing (50+){/ts}</h2>
<div class="messages status no-popup">
  <div class="help-block" id="help">
    {ts}You can mail 50+ volunteers in 1 bulk mailing. If you need to mail less you can but you could also use Send Email (max. 50) action.{/ts}
  </div>
  <h3>{$select_txt}</h3>
    {if !empty($volunteers)}
      <table>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}BioResource ID{/ts}</th>
          <th>{ts}Participant ID{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Email{/ts}</th>
        </tr>
          {foreach from=$volunteers key=volunteer_id item=volunteer}
            <tr id='volunteerid{$volunteer_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$volunteer.display_name}</td>
              <td>{$volunteer.bioresource_id}</td>
              <td>{$volunteer.participant_id}</td>
              <td>{$volunteer.study_participant_id}</td>
              <td>{$volunteer.eligible_status}</td>
              <td>{$volunteer.email}</td>
            </tr>
          {/foreach}
      </table>
    {/if}
  <p>{$count_selected_txt}</p>
  <h3>{$invalid_txt}</h3>
    {if !empty($invalids)}
      <table>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}BioResource ID{/ts}</th>
          <th>{ts}Participant ID{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Reason{/ts}</th>
        </tr>
          {foreach from=$invalids key=contact_id item=invalid}
            <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$invalid.display_name}</td>
              <td>{$invalid.bioresource_id}</td>
              <td>{$invalid.participant_id}</td>
              <td>{$invalid.study_participant_id}</td>
              <td>{$invalid.eligible_status}</td>
              <td>{$invalid.reason}</td>
            </tr>
          {/foreach}
      </table>
    {/if}
  <p>{$count_invalid_txt}</p>
</div>
<p>
<div class="form-item">
    {$form.buttons.html}
</div>

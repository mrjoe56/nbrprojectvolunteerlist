{* Confirm contacts and select template for InviteByEmail *}
<h2>{ts}Invite by Bulk Mail (50+){/ts}</h2>
<div class="messages status no-popup">
  <div class="help-block" id="help">
    {ts}You can invite 50+ volunteers in 1 bulk mailing. If you need to mail less you can but you could also use the Invite By Email (max. 50) action.{/ts}
  </div>
  <h3>{$invited_txt}</h3>
    {if !empty($invited)}
      <table>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}BioResource ID{/ts}</th>
          <th>{ts}Participant ID{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Email{/ts}</th>
          <th>Remarks</th>
        </tr>
          {foreach from=$invited key=contact_id item=invite}
            <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$invite.display_name}</td>
              <td>{$invite.bioresource_id}</td>
              <td>{$invite.participant_id}</td>
              <td>{$invite.study_participant_id}</td>
              <td>{$invite.eligible_status}</td>
              <td>{$invite.email}</td>
              <td>{$invite.remarks}</td>
            </tr>
          {/foreach}
      </table>
    {/if}
  <p>{$count_invited_txt}</p>
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

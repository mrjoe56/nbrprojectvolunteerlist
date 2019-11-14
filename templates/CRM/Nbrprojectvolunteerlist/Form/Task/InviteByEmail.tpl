{* Confirm contacts and select template for InviteByEmail *}
<h2>{ts}Invite by Email{/ts}</h2>
<div class="messages status no-popup">
  <div class="crm-form-block">
    <h3>{$template_txt}</h3>
    <div class="crm-section">
      <div class="label">{$form.template_id.label}</div>
      <div class="content">{$form.template_id.html}</div>
      <div class="clear"></div>
    </div>
  </div>
  <h3>{$invited_txt}</h3>
    {if !empty($invited)}
      <table>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}BioResource ID{/ts}</th>
          <th>{ts}Study/Participant ID{/ts}</th>
          <th>{ts}Eligibility{/ts}</th>
          <th>{ts}Email{/ts}</th>
        </tr>
          {foreach from=$invited key=contact_id item=invite}
            <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$invite.display_name}</td>
              <td>{$invite.bioresource_id}</td>
              <td>{$invite.study_participant_id}</td>
              <td>{$invite.eligible_status}</td>
              <td>{$invite.email}</td>
            </tr>
          {/foreach}
      </table>
    {/if}
  <p>{$count_invited_txt}</p>
</div>
<p>
<div class="form-item">
    {$form.buttons.html}
</div>

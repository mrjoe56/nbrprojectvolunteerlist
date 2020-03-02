{* Confirm export of volunteers *}
<h2>{ts}Export to CSV for External Researchers{/ts}</h2>
<div class="messages export-external no-popup">
  <h3>{$selected_txt}</h3>
  {if !empty($selected)}
    <table>
      <tr>
        <th>{ts}First Name{/ts}</th>
        <th>{ts}Last Name{/ts}</th>
        <th>{ts}Study/Part ID{/ts}</th>
        <th>{ts}Recall Group{/ts}</th>
        <th>{ts}Age{/ts}</th>
        <th>{ts}Gender{/ts}</th>
        <th>{ts}Birth Date{/ts}</th>
        <th>{ts}Email{/ts}</th>
        <th>{ts}Phone{/ts}</th>
        <th>{ts}Mobile{/ts}</th>
        <th>{ts}Street Address{/ts}</th>
        <th>{ts}City{/ts}</th>
        <th>{ts}County{/ts}</th>
        <th>{ts}Postcode{/ts}</th>
        <th>{ts}Date Invited{/ts}</th>
      </tr>
      {foreach from=$selected key=contact_id item=vol}
        <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
          <td>{$vol.first_name}</td>
          <td>{$vol.last_name}</td>
          <td>{$vol.study_participant_id}</td>
          <td>{$vol.recall_group}</td>
          <td>{$vol.age}</td>
          <td>{$vol.gender}</td>
          <td>{$vol.birth_date}</td>
          <td>{$vol.email}</td>
          <td>{$vol.phone}</td>
          <td>{$vol.mobile}</td>
          <td>{$vol.street_address}</td>
          <td>{$vol.city}</td>
          <td>{$vol.county}</td>
          <td>{$vol.postal_code}</td>
          <td>{$vol.date_invited}</td>
        </tr>
      {/foreach}
    </table>
  {/if}
  <p>{$count_selected_txt}</p>
  <hr />
  <h3>{$invalid_txt}</h3>
  {if !empty($invalids)}
    <table>
      <tr>
        <th>{ts}First Name{/ts}</th>
        <th>{ts}Last Name{/ts}</th>
        <th>{ts}Study/Part ID{/ts}</th>
        <th>{ts}Recall Group{/ts}</th>
        <th>{ts}Age{/ts}</th>
        <th>{ts}Gender{/ts}</th>
        <th>{ts}Birth Date{/ts}</th>
        <th>{ts}Email{/ts}</th>
        <th>{ts}Phone{/ts}</th>
        <th>{ts}Mobile{/ts}</th>
        <th>{ts}Street Address{/ts}</th>
        <th>{ts}City{/ts}</th>
        <th>{ts}County{/ts}</th>
        <th>{ts}Postcode{/ts}</th>
        <th>{ts}Date Invited{/ts}</th>
      </tr>
      {foreach from=$invalids key=contact_id item=invalid}
        <tr id='contactid{$contact_id}' class="{cycle values="odd-row,even-row"}">
          <td>{$invalid.first_name}</td>
          <td>{$invalid.last_name}</td>
          <td>{$invalid.study_participant_id}</td>
          <td>{$invalid.recall_group}</td>
          <td>{$invalid.age}</td>
          <td>{$invalid.gender}</td>
          <td>{$invalid.birth_date|truncate:10:''|crmDate}</td>
          <td>{$invalid.email}</td>
          <td>{$invalid.phone}</td>
          <td>{$invalid.mobile}</td>
          <td>{$invalid.street_address}</td>
          <td>{$invalid.city}</td>
          <td>{$invalid.county}</td>
          <td>{$invalid.postal_code}</td>
          <td>{$invalid.date_invited|truncate:10:''|crmDate}</td>
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

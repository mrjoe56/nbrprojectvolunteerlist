<table class="form-layout-compressed is_nbr_invite">
  <tbody>
    <tr>
      <td class="label-left">{$form.is_nbr_invite.label}</td>
      <td>{$form.is_nbr_invite.html}</td>
    </tr>
  </tbody>
</table>
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
        </tr>
        </thead>
        <tbody>
        {if !empty($invalid_ids)}
          {foreach from=$invalid_ids key=invalid_id item=invalid}
            <tr id='invalid{$invalid_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$invalid.display_name}</td>
              <td>{$invalid.study_status}</td>
              <td>{$invalid.eligible_status}</td>
            </tr>
          {/foreach}
        {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>
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
        {if !empty($invited_ids)}
          {foreach from=$invited_ids key=invited_id item=invited}
            <tr id='invited{$invited_id}' class="{cycle values="odd-row,even-row"}">
              <td>{$invited.display_name}</td>
              <td>{$invited.study_status}</td>
              <td>{$invited.eligible_status}</td>
            </tr>
          {/foreach}
        {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>
{literal}
  <script type="text/javascript">
    cj(".crm-pdf-format-accordion").each(function() {
      //cj(this).prepend(cj(".nbr_invalids"));
      cj(".is_nbr_invite").insertBefore(cj(this));
      cj(".nbr_invited").insertBefore(cj(this));
      cj(".nbr_invalids").insertBefore(cj(this));
      //cj(this).prepend(cj(".is_nbr_invite"));
    });
    cj("#is_nbr_invite").change(function() {
      var inviteChecked = cj("#is_nbr_invite:checked").length;
      if (inviteChecked) {
        cj(".nbr_invited").show();
        cj(".nbr_invalids").show();
      }
      else {
        cj(".nbr_invited").show();
        cj(".nbr_invalids").hide();
      }
    });
    cj(document).ready(function() {
      var inviteChecked = cj("#is_nbr_invite:checked").length;
      if (inviteChecked) {
        cj(".nbr_invited").show();
        cj(".nbr_invalids").show();
      }
      else {
        cj(".nbr_invited").show();
        cj(".nbr_invalids").hide();
      }
    });
  </script>
{/literal}

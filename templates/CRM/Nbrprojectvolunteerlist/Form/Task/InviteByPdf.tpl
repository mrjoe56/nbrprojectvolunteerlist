<table class="form-layout-compressed is_nbr_invite">
  <td class="label-left">{$form.is_nbr_invite.label}</td>
  <td>{$form.is_nbr_invite.html}</td>
</table>
{literal}
  <script type="text/javascript">
    cj(".crm-pdf-format-accordion").each(function() {
      cj(this).prepend(cj(".is_nbr_invite"));
    });
  </script>
{/literal}

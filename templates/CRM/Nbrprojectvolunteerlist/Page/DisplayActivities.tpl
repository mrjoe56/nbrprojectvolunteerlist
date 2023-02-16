<h1> Latest activities</h1>

<table id="nbractivity-table" class="display">
    <thead>
    <tr>
        <th id="sortable">{ts}ID{/ts}</th>

        <th id="sortable">{ts}Activity date{/ts}</th>
        <th id="sortable">{ts}Subject{/ts}</th>
        <th id="sortable">{ts}Type{/ts}</th>
        <th id="sortable">{ts}Status{/ts}</th>
        <th id="sortable">{ts}Details{/ts}</th>
        <th id="sortable">{ts}Assignee{/ts}</th>

    </tr>
    </thead>
    <tbody>
    {assign var="row_class" value="odd-row"}
    {foreach from=$activities key=activity_id item=activity}
        <tr id="activity-{$activity_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class} nbr-study-row">
            <td>{$activity.id}</td>
            <td>{$activity.activity_date}</td>
            <td>{$activity.activity_subject}</td>
            <td>{$activity.activity_type}</td>
            <td>{$activity.activity_status}</td>
            <td>{$activity.activity_notes}</td>
            <td>{$activity.activity_assignee}</td>

        </tr>
    {/foreach}
    </tbody>
</table>
</div>
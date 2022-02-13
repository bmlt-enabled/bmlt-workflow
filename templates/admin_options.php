<?php
echo <<<END
<div class="wrap">
<script>
jQuery(document).ready(function($) {
    $("#bmaw-service-committee-table tbody").on("click", "tr td:nth-child(4)", function(event){
        var rowCount = $('#bmaw-service-committee-table tr').length-2;
        var clicked = $(this).closest('tr').index();
        console.log("table length "+rowCount+" row clicked "+clicked);
        if (clicked == rowCount)
        {
            console.log("add row")
            $('#bmaw-service-committee-table > tbody > tr').eq(rowCount-1).after(`<tr>
            <td><input type="text" name="bmaw_service_committee_option_array['+rowCount+'][name]" value=""/></td>
            <td><input type="text" name="bmaw_service_committee_option_array['+rowCount+'][e1]" value=""/></td>
            <td><input type="text" name="bmaw_service_committee_option_array['+rowCount+'][e2]" value=""/></td>
            <td><span id="bmaw-service-committee-new-row" class="dashicons dashicons-remove"></span></td>
            </tr>`);
        }
        else
        {
        $('#bmaw-service-committee-table > tbody > tr').eq(clicked).remove()
            console.log("delete row")
        }
    });
});
</script>
<h1>BMLT Meeting Admin Workflow Settings</h1>
<form method="post" action="options.php">
END;
        
    settings_fields( 'bmaw-settings-group' ); // settings group name
    do_settings_sections( 'bmaw-settings' ); // just a page slug

    submit_button();

echo '</form></div>';

?>


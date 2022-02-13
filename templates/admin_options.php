<?php
echo <<<END
<div class="wrap">
<script>
jQuery(document).ready(function($) {

    $('#bmaw-service-committee-table tr td:eq(4)').click(function() {
        console.log("got a click")
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


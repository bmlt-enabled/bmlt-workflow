<?php
echo <<<END
<div class="wrap">
<script>
jQuery(document).ready(function($) {

    $(".committeetable").on("click", "tr", function(){
        var cb = $(this).find("input:checkbox")
        if(cb) {
            cb.prop( "checked", true )
        }
    });

});
</script>
<h1>BMLT Meeting Admin Workflow Settings</h1>
<form method="post" action="options.php">';
END;
        
    settings_fields( 'bmaw-settings-group' ); // settings group name
    do_settings_sections( 'bmaw-settings' ); // just a page slug
    submit_button();

echo '</form></div>';

?>


<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);

echo '<div class="wrap"><script>';
echo "var bmaw_service_form_array = ". $js_array . ";</script>";
echo '<script src="'.plugin_dir_url(__FILE__).'js/admin_page.js"</script>';

echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

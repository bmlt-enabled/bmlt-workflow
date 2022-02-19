<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);

echo '<div class="wrap"><script>';
echo "var bmaw_service_form_array = ". $js_array . ";</script>";
// echo '<script src="'.THIS_PLUGIN_URL.'js/admin_page.js"</script>';
// echo '<script src="http://54.153.167.239/flop/wp-content/plugins/meeting-admin-workflow/js/admin_page.js?ver=1645231395"></script>';
echo '<script src="'.esc_url( plugins_url( 'js/admin_page.js', dirname(__FILE__))).'"</script>';
echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

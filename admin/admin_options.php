<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);
$test_result = get_option('bmaw_test_successful','failed');
if($test_result!='succeeded')
{
    $test_result = 'failed';
}
echo '<div class="wrap">';
echo '<script>var bmaw_service_form_array = '. $js_array . '</script>';
echo '<script>var bmaw_test_successful = "'. $test_result .'"</script>';
echo '<script src="'.esc_url( plugins_url( 'js/admin_page.js', dirname(__FILE__))).'"></script>';
echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);
$test_result = get_option('bmaw_bmlt_test_status','failure');
$bmaw_close_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html'));
$bmawt_other_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html'));
$bmaw_new_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html'));
$bmaw_existing_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html'));
$bmaw_fso_email_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_fso_email_template.html'));

echo '<div class="wrap">';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.10/clipboard.min.js"></script>';
echo '<script>var clipboard = new ClipboardJS(".clipboard-button");</script>';
echo '<script>var bmaw_service_form_array = '. $js_array . '</script>';
echo '<script>var test_status = "'. $test_result .'"</script>';

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_new_meeting_template_default">'.$bmaw_new_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_existing_meeting_template_default">'.$bmaw_existing_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_close_meeting_template_default">'.$bmaw_close_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_other_meeting_template_default">'.$bmaw_other_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_fso_email_template_default">'.$bmaw_fso_email_template_default.'</textarea></div>';
echo '<script src="'.esc_url( plugins_url( 'js/admin_page.js', dirname(__FILE__))).'"></script>';
echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

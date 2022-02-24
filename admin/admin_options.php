<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);
$bmaw_close_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html'));
$bmawt_other_meeting_template_defaul = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html'));
$bmaw_new_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html'));
$bmaw_existing_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html'));

echo '<div class="wrap">';
echo '<script src="'.esc_url( plugins_url( 'js/clipboard.js', dirname(__FILE__))).'"></script>';
echo '<script>var clipboard = new ClipboardJS(".clipboard-button");</script>';
echo '<script>var bmaw_service_form_array = '. $js_array . '</script>';

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_new_meeting_template_default">'.$bmaw_new_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_existing_meeting_template_default">'.$bmaw_existing_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_close_meeting_template_default">'.$bmaw_close_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_other_meeting_template_default">'.$bmawt_other_meeting_template_defaul.'</textarea></div>';
echo '<script src="'.esc_url( plugins_url( 'js/admin_page.js', dirname(__FILE__))).'"></script>';
echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

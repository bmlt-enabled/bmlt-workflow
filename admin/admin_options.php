<?php

$arr = get_option('bmaw_service_committee_option_array');
$js_array = json_encode($arr);
$test_result = get_option('bmaw_test_successful','failed');
$bmaw_close_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html'));
$bmawt_other_meeting_template_defaul = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html'));
$bmaw_new_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html'));
$bmaw_existing_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html'));
dbg("close meeting template = ".$bmaw_close_meeting_template_default);
if($test_result!='succeeded')
{
    $test_result = 'failed';
}

echo '<div class="wrap">';
echo '<script src="'.esc_url( plugins_url( 'js/clipboard.js', dirname(__FILE__))).'"></script>';
echo '<script>clipboard = new ClipboardJS(".clipboard-button");</script>';

echo '<script>var bmaw_service_form_array = '. $js_array . '</script>';
echo '<script>var bmaw_test_successful = "'. $test_result .'"</script>';
// echo '<script>var default_close_meeting_email_template = `'. $default_close_meeting_email_template .'`</script>';
// echo '<script>var default_other_meeting_email_template = `'. $default_other_meeting_email_template .'`</script>';
// echo '<script>var default_new_meeting_email_template = `'. $default_new_meeting_email_template .'`</script>';
//echo '<script>var default_existing_meeting_email_template = `'. $default_existing_meeting_email_template .'`</script>';
echo '<textarea style="display: none;" id="bmaw_new_meeting_template_default">'.$bmaw_new_meeting_template_default.'</textarea>';
echo '<textarea style="display: none;" id="bmaw_existing_meeting_template_default">'.$bmaw_existing_meeting_template_default.'</textarea>';
echo '<textarea style="display: none;" id="bmaw_close_meeting_template_default">'.$bmaw_close_meeting_template_default.'</textarea>';
echo '<textarea style="display: none;" id="bmaw_other_meeting_template_default">'.$bmawt_other_meeting_template_defaul.'</textarea>';
echo '<script src="'.esc_url( plugins_url( 'js/admin_page.js', dirname(__FILE__))).'"></script>';
echo '<form method="post" action="options.php">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

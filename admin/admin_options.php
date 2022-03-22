<?php

if (!defined('ABSPATH')) exit; // die if being called directly

$bmaw_close_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html'));
$bmaw_other_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html'));
$bmaw_new_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html'));
$bmaw_existing_meeting_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html'));
$bmaw_fso_email_template_default = htmlentities(file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_fso_email_template.html'));

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_new_meeting_template_default">'.$bmaw_new_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_existing_meeting_template_default">'.$bmaw_existing_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_close_meeting_template_default">'.$bmaw_close_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_other_meeting_template_default">'.$bmaw_other_meeting_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmaw_fso_email_template_default">'.$bmaw_fso_email_template_default.'</textarea></div>';

wp_nonce_field('wp_rest', '_wprestnonce');
echo '<hr class="wp-header-end">';
echo '<div class="wrap">';
echo '<form method="post" action="#">';

settings_fields( 'bmaw-settings-group' );
do_settings_sections( 'bmaw-settings' );

submit_button();

echo '</form></div>';

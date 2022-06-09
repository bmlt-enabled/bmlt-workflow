<?php

if (!defined('ABSPATH')) exit; // die if being called directly

use wbw\WBW_Debug;
use wbw\WBW_WP_Options;

$WBW_WP_Options = new WBW_WP_Options();

$wbw_submitter_email_template_default = htmlentities(file_get_contents(WBW_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
$wbw_fso_email_template_default = htmlentities(file_get_contents(WBW_PLUGIN_DIR . 'templates/default_fso_email_template.html'));

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="wbw_submitter_email_template_default">' . $wbw_submitter_email_template_default . '</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="wbw_fso_email_template_default">' . $wbw_fso_email_template_default . '</textarea></div>';
echo '<div class="wbw_banner"></div>';

wp_nonce_field('wp_rest', '_wprestnonce');
echo '<hr class="wbw-error-message">';
echo '<div class="wrap">';
echo '<form method="post" action="options.php">';
settings_fields('wbw-settings-group');
do_settings_sections('wbw-settings');

submit_button();

echo '</form></div>';
?>

<div id="wbw_bmlt_configuration_dialog" class="hidden" style="max-width:800px">
    <div class="options_dialog_wbw_error_message"></div>
    <br>
    <div class="options_dialog_wbw_info_text">
        <br>Enter your BMLT server address, and a BMLT username and password.
        <br>
        <br>
    </div>

    <br><label for="wbw_bmlt_server_address"><b>Server Address:</b></label>
    <input type="url" size="50" id="wbw_bmlt_server_address" name="wbw_bmlt_server_address" value="<?php echo $WBW_WP_Options->wbw_get_option('wbw_bmlt_server_address') ?>" />
    <br><label for="wbw_bmlt_username"><b>BMLT Username:</b></label>
    <input type="text" size="50" id="wbw_bmlt_username" name="wbw_bmlt_username" value="<?php echo $WBW_WP_Options->wbw_get_option('wbw_bmlt_username') ?>" />
    <br><label for="wbw_bmlt_password"><b>BMLT Password:</b></label>
    <input type="password" size="50" id="wbw_bmlt_password" name="wbw_bmlt_password" />
    <br><br>
    <div class="options_dialog_wbw_info_text">
        <br>The BMLT username and password is used to action meeting approvals/rejections as well as perform any BMLT related actions on the Wordpress users behalf.
        <br><br>This user must be configured as a service body administrator and have access within BMLT to edit all service bodies that are used in WBW form submissions.
        <br>
        <br>The server address is the full URL to your server installation. For example: <code>https://na.test.zzz/main_server/</code>
        <br>
        <br>
    </div>
</div>

<div id="wbw_bmlt_erase_warning_dialog" class="hidden" style="max-width:800px">
    <div class="options_dialog_wbw_error_message"></div>
    <br>
    <div class="options_dialog_wbw_warning_text">
        <br>
        <br>WARNING: If you proceed, your existing plugin configuration, settings and service bodies will be removed.
        <br>
        <br>
    </div>
</div>
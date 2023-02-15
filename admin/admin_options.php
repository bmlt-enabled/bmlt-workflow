<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.


if ((!defined('ABSPATH')&&(!defined('BMLTWF_RUNNING_UNDER_PHPUNIT')))) exit; // die if being called directly

use bmltwf\BMLTWF_Debug;

$bmltwf_submitter_email_template_default = htmlentities(file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
$bmltwf_fso_email_template_default = htmlentities(file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_fso_email_template.html'));

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmltwf_submitter_email_template_default">' . esc_textarea($bmltwf_submitter_email_template_default) . '</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="bmltwf_fso_email_template_default">' . esc_textarea($bmltwf_fso_email_template_default) . '</textarea></div>';
echo '<div class="bmltwf_banner"></div>';

wp_nonce_field('wp_rest', '_wprestnonce');
echo '<hr class="bmltwf-error-message">';
echo '<div class="wrap"><h5 align="right">Plugin Version: '.BMLTWF_PLUGIN_VERSION.'</h5>';
echo '<p><h4 align="center">BMLT Workflow issues, bugs or suggestions please raise them on github (<a href="https://github.com/bmlt-enabled/bmlt-workflow/issues">Issues</a>) or chat to us on bmlt-enabled slack, <b>#wordpress-bmlt-workflow</b>!</h4>';
echo '<form id="bmltwf_options_form" method="post" action="options.php">';
settings_errors();

settings_fields('bmltwf-settings-group');
do_settings_sections('bmltwf-settings');

submit_button();

echo '</form></div>';
?>

<div id="bmltwf_bmlt_configuration_dialog" class="hidden" style="max-width:800px">
    <div class="options_dialog_bmltwf_error_message"></div>
    <br>
    <div class="options_dialog_bmltwf_info_text">
        <br>Enter your BMLT Root Server address, and a BMLT Root Server username and password.
        <br>
        <br>
    </div>

    <br><label for="bmltwf_bmlt_server_address"><b>BMLT Root Server Address:</b></label>
    <input type="url" size="50" id="bmltwf_bmlt_server_address" name="bmltwf_bmlt_server_address" value="<?php echo esc_url_raw(get_option('bmltwf_bmlt_server_address')) ?>" />
    <div id="bmltwf_bmlt_server_address_test_yes" style="display: inline-block;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span></div>
    <div id="bmltwf_bmlt_server_address_test_no" style="display: inline-block;" ><span class="dashicons dashicons-no" style="color: red;"></span></div>

    <br><label for="bmltwf_bmlt_username"><b>Username:</b></label>
    <input type="text" size="50" id="bmltwf_bmlt_username" name="bmltwf_bmlt_username" value="<?php echo esc_attr(get_option('bmltwf_bmlt_username')) ?>" />
    <div id="bmltwf_bmlt_login_test_yes" style="display: inline-block;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span></div>
    <div id="bmltwf_bmlt_login_test_no" style="display: inline-block;" ><span class="dashicons dashicons-no" style="color: red;"></span></div>

    <br><label for="bmltwf_bmlt_password"><b>Password:</b></label>
    <input type="password" size="50" id="bmltwf_bmlt_password" name="bmltwf_bmlt_password" />

    <br><br>
    <div class="options_dialog_bmltwf_info_text">
        <br>The BMLT root server username and password is used to action meeting approvals/rejections as well as perform any BMLT related actions on the Wordpress users behalf.
        <br><br>This user must be configured as a service body administrator and have access within the BMLT Root Server to edit all service bodies that are used in BMLTWF form submissions.
        <br>
        <br>The server address is the full URL to your BMLT Root Server installation. For example: <code>https://na.test.zzz/main_server/</code>
        <br>
        <br>
    </div>
</div>

<div id="bmltwf_restore_warning_dialog" class="hidden" style="max-width:800px">
    <div class="options_dialog_bmltwf_error_message"></div>
    <br>
    <div class="options_dialog_bmltwf_warning_text">
        <br>WARNING: If you proceed with the restore, your existing plugin configuration, settings and service bodies will be removed.
        <br><br>Are you sure you wish to do this?
        <br><br>
    </div>
    
<div id="bmltwf_bmlt_change_server_warning_dialog" class="hidden" style="max-width:800px">
<div class="options_dialog_bmltwf_warning_text">

    <br>WARNING: Changing the BMLT Root Server settings will remove your service body configuration and existing submissions within the plugin.
    <br><br>Use the BACKUP option before pressing Ok if you do not wish to lose your submissions.
    <br><br>If you press Ok, your service bodies, service body permissions and ALL SUBMISSIONS will be removed.
    <br><br>Are you sure you wish to do this?
    <br><br>
    <label for="yesimsure">Yes I'm sure!</label>
    <input type="checkbox" id="yesimsure" name="yesimsure">
    <br><br>
    <div>

</div>
<?php

if (!defined('ABSPATH')) exit; // die if being called directly

use wbw\Debug;

$wbw_submitter_email_template_default = htmlentities(file_get_contents(WBW_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
$wbw_fso_email_template_default = htmlentities(file_get_contents(WBW_PLUGIN_DIR . 'templates/default_fso_email_template.html'));

echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="wbw_submitter_email_template_default">'.$wbw_submitter_email_template_default.'</textarea></div>';
echo '<div style="position:absolute; top:0; left:-500px;"><textarea rows="1" cols="2" id="wbw_fso_email_template_default">'.$wbw_fso_email_template_default.'</textarea></div>';

wp_nonce_field('wp_rest', '_wprestnonce');
echo '<hr class="wp-header-end">';
echo '<div class="wrap">';
echo '<form method="post" action="options.php">';
settings_fields( 'wbw-settings-group' );
do_settings_sections( 'wbw-settings' );

submit_button();

echo '</form></div>';
?>

<div id="wbw_bmlt_configuration_dialog" class="hidden" style="max-width:800px">
<br><label for="wbw_bmlt_server_address"><b>Server Address:</b></label>
<input type="url" size="50" id="wbw_bmlt_server_address" name="wbw_bmlt_server_address" value="<? echo $wbw_bmlt_username ?>"/>
<br><label for="wbw_bmlt_username"><b>BMLT Username:</b></label>
<input type="text" size="50" id="wbw_bmlt_username" name="wbw_bmlt_username" value="<? echo $wbw_bmlt_username ?>"/>
<br><label for="wbw_bmlt_password"><b>BMLT Password:</b></label>
<input type="password" size="50" id="wbw_bmlt_password" name="wbw_bmlt_password"/>
<button type="button" id="wbw_test_bmlt_server">Test BMLT Configuration</button>
</div>

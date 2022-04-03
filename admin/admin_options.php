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
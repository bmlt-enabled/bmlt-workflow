<?php

if (!defined('ABSPATH')) exit; // die if being called directly

$wbw_bmlt_test_status = get_option('wbw_bmlt_test_status', "failure");
if ($wbw_bmlt_test_status != "success") {
    wp_die("<h4>WBW Plugin Error: BMLT Server not configured and tested.</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Service Body Configuration</h2>
    <hr class="wp-header-end">
    <div class="wbw_info_text">
    <br>Service bodies are retrieved from BMLT using the BMLT details configured on the option page. 
    <br><br>You can configure which service areas are visible to the end-users using the <code>Display on end-user Form</code> checkbox.
    <br><br>You can select users from your Wordpress userlist and grant them access to your service areas in the <code>Wordpress Users with Access</code> column. 
    These users will only be given access to the submission admin page, and only submissions from their service areas will be visible to approve.
    <br><br>If you don't see service body email addresses and you have them configured in BMLT, you must set <code>$g_include_service_body_email_in_semantic = true</code> in your <code>server/config/comdef-config.inc.php</code> file on BMLT.
    </div>
    <br>
    <span class="spinner" id="wbw-form-spinner"></span>
                <table class="wbw-userlist-table" id="wbw-userlist-table" style="display: none;">
                <thead>
                    <tr>
                        <th class="wbw-userlist-header">Service Body</th>
                        <th class="wbw-userlist-header">Service Body Email</th>
                        <th class="wbw-userlist-header">Wordpress Users with Access</th>
                        <th class="wbw-userlist-header">Display on end-user Form</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="wbw_submit" style="display: none;">Save Settings</button><span class="spinner" id="wbw-submit-spinner"></span>
        </div>

<?php

if (!defined('ABSPATH')) exit; // die if being called directly

// if (!class_exists('BMLTIntegration')) {
//     require_once(BMAW_PLUGIN_DIR . 'admin/bmlt_integration.php');
// }

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Service Area Configuration</h2>
    <div class="bmaw_info_text">
    <br><br>Service areas are retrieved from BMLT using the BMLT details configured on the option page. 
    <br><br>You can configure which service areas are visible to the end-users using the <code>Display on end-user Form</code> checkbox.
    <br><br>You can select users from your Wordpress userlist and grant them access in the <code>Wordpress Users with Access</code> section. 
    These users will only be given access to the submission admin page, and only submissions from their service areas will be visible to approve.
    <br><br>
    </div>

    <span class="spinner" id="bmaw-form-spinner"></span>
                <table class="bmaw-userlist-table" id="bmaw-userlist-table" style="display: none;">
                <thead>
                    <tr>
                        <th class="bmaw-userlist-header">Service Area</th>
                        <th class="bmaw-userlist-header">Wordpress Users with Access</th>
                        <th class="bmaw-userlist-header">Display on end-user Form</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="bmaw_submit" style="display: none;">Save Settings</button><span class="spinner" id="bmaw-submit-spinner"></span>
        </div>

<?php

if (!defined('ABSPATH')) exit; // die if being called directly

if (!class_exists('BMLTIntegration')) {
    require_once(BMAW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Service Area Configuration</h2>
    <br><span class="spinner" id="bmaw-form-spinner"></span><br>
                <table class="bmaw-userlist-table" id="bmaw-userlist-table">
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
            <button id="bmaw_submit">Save Settings</button><span class="spinner" id="bmaw-submit-spinner"></span>
        </div>

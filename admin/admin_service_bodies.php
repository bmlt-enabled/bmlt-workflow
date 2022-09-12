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

$bmltwf_bmlt_test_status = get_option('bmltwf_bmlt_test_status', "failure");
if ($bmltwf_bmlt_test_status != "success") {
    wp_die("<h4>BMLTWF Plugin Error: BMLT Root Server not configured and tested.</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Service Body Configuration</h2>
    <hr class="bmltwf-error-message">
    <div class="bmltwf_info_text">
        <br>Service bodies are retrieved from BMLT Root Server using the BMLT Root Server details configured on the option page.
        <br><br>You can configure which service areas are visible to the end-users using the <code>Display on end-user Form</code> checkbox.
        <br><br>You can select users from your Wordpress userlist and grant them access to your service areas in the <code>Wordpress Users with Access</code> column.
        These users will only be given access to the submission admin page, and only submissions from their service areas will be visible to approve.
        <br><br>Note: Settings will only be applied when the 'Save Settings' button is pressed.
        <br><br>
    </div>
    <br>
    <span class="spinner" id="bmltwf-form-spinner"></span>
    <table class="bmltwf-userlist-table" id="bmltwf-userlist-table" style="display: none;">
        <thead>
            <tr>
                <th class="bmltwf-userlist-header">Service Body</th>
                <th class="bmltwf-userlist-header">Description</th>
                <th class="bmltwf-userlist-header">Wordpress Users with Access</th>
                <th class="bmltwf-userlist-header">Display on end-user Form</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <button id="bmltwf_submit" style="display: none;">Save Settings</button><span class="spinner" id="bmltwf-submit-spinner"></span>
</div>
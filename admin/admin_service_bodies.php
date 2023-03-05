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
    wp_die("<h4>".__('BMLTWF Plugin Error: BMLT Root Server not configured and tested.','bmlt-workflow')."</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2><?php echo __( 'Service Body Configuration', 'bmlt-workflow' ); ?></h2>
    <hr class="bmltwf-error-message">
    <div class="bmltwf_info_text">
        <br><?php echo __( 'Service bodies are retrieved from BMLT Root Server using the BMLT Root Server details configured on the option page.', 'bmlt-workflow' ); ?>
        <br><br><?php echo __( 'You can configure which service areas are visible to the end-users using the <code>Display on end-user Form</code> checkbox.', 'bmlt-workflow' ); ?>
        <br><br><?php echo __( 'You can select users from your Wordpress userlist and grant them access to your service areas in the <code>Wordpress Users with Access</code> column. These users will only be given access to the submission admin page, and only submissions from their service areas will be visible to approve.', 'bmlt-workflow' ); ?>
        <br><br><?php echo __( 'Wordpress admins or multisite superadmins will always have access to all submissions, so there is no need to add them individually', 'bmlt-workflow' ); ?>
        <br><br><?php echo __( "Note: Settings will only be applied when the 'Save Settings' button is pressed.", 'bmlt-workflow' ); ?>
        <br><br>
    </div>
    <br>
    <span class="spinner" id="bmltwf-form-spinner"></span>
    <table class="bmltwf-userlist-table" id="bmltwf-userlist-table" style="display: none;">
        <thead>
            <tr>
                <th class="bmltwf-userlist-header"><?php echo __( 'Service Body', 'bmlt-workflow' ); ?></th>
                <th class="bmltwf-userlist-header"><?php echo __( 'Description', 'bmlt-workflow' ); ?></th>
                <th class="bmltwf-userlist-header"><?php echo __( 'Wordpress Users with Access', 'bmlt-workflow' ); ?></th>
                <th class="bmltwf-userlist-header"><?php echo __( 'Display on end-user Form', 'bmlt-workflow' ); ?></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <button id="bmltwf_submit" style="display: none;"><?php echo __( 'Save Settings', 'bmlt-workflow' ); ?></button><span class="spinner" id="bmltwf-submit-spinner"></span>
</div>
<?php

if (!defined('ABSPATH')) exit; // die if being called directly

wp_nonce_field('wp_rest', '_wprestnonce');

?>
<!-- Approve dialog -->
<div id="bmaw_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmaw_submission_approve_dialog_textarea">Approval note:</label>
    <textarea class='dialog_textarea' id="bmaw_submission_approve_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this approval for the submitter'></textarea>
    <p>You can use the quickedit function to make any extra changes before approval.</p>
    <p>Are you sure you would like to approve the submission?</p>
</div>

<!-- Delete dialog -->
<div id="bmaw_submission_delete_dialog" class="hidden" style="max-width:800px">
    <p>This change cannot be undone. Use this to remove an entirely unwanted submission from the list.</p>
    <p>Are you sure you would like to delete the submission completely?</p>
</div>

<!-- Reject dialog -->
<div id="bmaw_submission_reject_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmaw_submission_reject_dialog_textarea">Rejection note:</label>
    <textarea class='dialog_textarea' id="bmaw_submission_reject_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this rejection for the submitter'></textarea>
    <p>Are you sure you would like to reject this submission?</p>
</div>

<!-- "update_reason" => array("text", true),
        "first_name" => array("text", true),
        "last_name" => array("text", true),
        "meeting_name" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "start_time" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "duration_time" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_text" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_street" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_info" => array("text", false),
        "location_municipality" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_province" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_postal_code_1" => array("number", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "weekday_tinyint" => array("weekday", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "service_body_bigint" => array("bigint", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "virtual_meeting_link" => array("url", false),
        "email_address" => array("email", true),
        "contact_number_confidential" => array("text", false),
        // "time_zone",
        "format_shared_id_list" => array("text",  $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "additional_info" => array("textarea", false),
        "starter_kit_postal_address" => array("textarea", false),
        "starter_kit_required" => array("text", false),
        "other_reason" => array("textarea", $reason_other_bool) -->

<!-- Quickedit dialog -->
<div id="bmaw_submission_quickedit_dialog" class="hidden bmaw_submission_quickedit_dialog">
        <div class="bmaw-grid-col1">
            <label for="meeting_name">Meeting Name</label>
            <input type="text" name="meeting_name" id="meeting_name" class="bmaw_submission_input_half">
            <label for="start_time">Start Time</label>
            <input type="time" name="start_time" id="start_time" class="bmaw_submission_input_half" >
            <label for="duration_time">Duration</label>
            <input type="text" name="duration_time" id="duration_time" required>
            <label for="location_text">Location</label>
            <input type="text" name="location_text" id="location_text" required>
            <label for="location_street">Street</label>
            <input type="text" name="location_street" id="location_street" required>
            <label for="location_info">Location Info</label>
            <input type="text" name="location_info" id="location_info" required>
            <label for="location_municipality">Municipality</label>
            <input type="text" name="location_municipality" id="location_municipality" required>
        </div>
        <div class="bmaw-grid-col2">
            <label for="col2a">col2a</label>
            <input type="text" name="col2a" id="col2a" required>
            <label for="col2b">col2b</label>
            <input type="text" name="col2b" id="col2b" required>
            <label for="col2c">col2c</label>
            <input type="text" name="col2c" id="col2c" required>
            <label for="col2d">col2d</label>
            <input type="text" name="col2d" id="col2d" required>
            <label for="col2e">col2e</label>
            <input type="text" name="col2e" id="col2e" required>
            <label for="col2f">col2f</label>
            <input type="text" name="col2f" id="col2f" required>
        </div>
</div>


<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Meeting Submissions</h2>
    <hr class="wp-header-end">
    <div class="dt-container">
        <table id="dt-submission" class="display" style="width:90%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Submitter Name</th>
                    <th>Submitter Email</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Submitter Name</th>
                    <th>Submitter Email</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
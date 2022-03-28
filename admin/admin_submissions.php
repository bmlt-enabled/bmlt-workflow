<?php

if (!defined('ABSPATH')) exit; // die if being called directly

wp_nonce_field('wp_rest', '_wprestnonce');

?>
<!-- Approve dialog -->
<div id="wbw_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="wbw_submission_approve_dialog_textarea">Approval note:</label>
    <div class="grow-wrap">
    <textarea class='dialog_textarea' id="wbw_submission_approve_close_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p>You can use the quickedit function to make any extra changes before approval.</p>
    <p>Are you sure you would like to approve the submission?</p>
</div>

<!-- Approve dialog -->
<div id="wbw_submission_approve_close_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="wbw_submission_approve_close_dialog_textarea">Approval note:</label>
    <div class="grow-wrap">
    <textarea class='dialog_textarea' id="wbw_submission_approve_close_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p>Choose whether you'd like the meeting to be deleted from BMLT, or marked as unpublished.</p>
    <input type='radio' name='close_action' id='close_unpublish'><label for='close_unpublish'>Unpublish</label>
    <input type='radio' name='close_action' id='close_delete'><label for='close_delete'>Delete</label>
</div>

<!-- Delete dialog -->
<div id="wbw_submission_delete_dialog" class="hidden" style="max-width:800px">
    <p>This change cannot be undone. Use this to remove an entirely unwanted submission from the list.</p>
    <p>Are you sure you would like to delete this submission completely?</p>
</div>

<!-- Reject dialog -->
<div id="wbw_submission_reject_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="wbw_submission_reject_dialog_textarea">Rejection note:</label>
    <div class="grow-wrap">
    <textarea class='dialog_textarea' id="wbw_submission_reject_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this reject for the submitter'></textarea>
    </div>
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
<div id="wbw_submission_quickedit_dialog" class="hidden">
    <div class="wbw_info_text">
    <br>Highlighted fields are from the user submission and will be used if the QuickEdit is saved or approved.
    <br><br>
    </div>
    <div class="wbw-sg">
    <div class="wbw-sg-col1">
        <div class="wbw-sg-col1-w1">
            <label for="quickedit_meeting_name">Meeting Name</label>
            <input type="text" name="quickedit_meeting_name" id="quickedit_meeting_name" class="quickedit-input">
            <label for="quickedit_format_shared_id_list">Meeting Formats</label>
            <select name="quickedit_format_shared_id_list" id="quickedit_format_shared_id_list" style="width: auto"></select>

        </div>

        <div class="wbw-sg-col1-t1">
            <label for="quickedit_start_time">Start Time</label>
            <input type="time" name="quickedit_start_time" id="quickedit_start_time" class="quickedit-input">

        </div>
        <div class="wbw-sg-col1-t2">
            <label for="quickedit_weekday_tinyint">Weekday</label>
            <select name="quickedit_weekday_tinyint" id="quickedit_weekday_tinyint">
                <option value="1">Sunday</option>
                <option value="2">Monday</option>
                <option value="3">Tuesday</option>
                <option value="4">Wednesday</option>
                <option value="5">Thursday</option>
                <option value="6">Friday</option>
                <option value="7">Saturday</option>
            </select>
        </div>
        <div class="wbw-sg-col1-t3">
            <label for="quickedit_duration_hours">Duration</label>
            <select id="quickedit_duration_hours">
                    <option value="00">0</option>
                    <option value="01" selected="selected">1</option>
                    <option value="02">2</option>
                    <option value="03">3</option>
                    <option value="04">4</option>
                    <option value="05">5</option>
                    <option value="06">6</option>
                    <option value="07">7</option>
                    <option value="08">8</option>
                    <option value="09">9</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select> h
                <select id="quickedit_duration_minutes">
                    <option value="00" selected="selected">0</option>
                    <option value="05">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                    <option value="30">30</option>
                    <option value="35">35</option>
                    <option value="40">40</option>
                    <option value="45">45</option>
                    <option value="50">50</option>
                    <option value="55">55</option>
                </select> m
        </div>
        <div class="wbw-sg-col1-w2">
            <label for="quickedit_email_address">Email Address</label>
            <input type="text" name="quickedit_email_address" id="quickedit_email_address" class="quickedit-input">
            <label for="quickedit_virtual_meeting_link">Virtual Meeting Link</label>
            <input type="text" name="quickedit_virtual_meeting_link" id="quickedit_virtual_meeting_link" class="quickedit-input">
        </div>
    </div>
    <div class="wbw-grid-col2">
        <label for="quickedit_location_text">Location</label>
        <input type="text" name="quickedit_quickedit_location_text" id="quickedit_location_text" class="quickedit-input">
        <label for="quickedit_location_street">Street</label>
        <input type="text" name="quickedit_location_street" id="quickedit_location_street" class="quickedit-input">
        <label for="quickedit_location_info">Location Info</label>
        <input type="text" name="quickedit_location_info" id="quickedit_location_info" class="quickedit-input">
        <label for="quickedit_location_municipality">Municipality</label>
        <input type="text" name="quickedit_location_municipality" id="quickedit_location_municipality" class="quickedit-input">
        <label for="quickedit_location_province">Province</label>
        <input type="text" name="quickedit_location_province" id="quickedit_location_province" class="quickedit-input">
        <label for="quickedit_location_postal_code_1">Post Code</label>
        <input type="text" name="quickedit_location_postal_code_1" id="quickedit_location_postal_code_1" class="quickedit-input">
    </div>
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
                    <th>Service Body</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                    <th>More Info</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Submitter Name</th>
                    <th>Submitter Email</th>
                    <th>Service Body</th>
                    <th>Change Summary</th>
                    <th>Submission Time</th>
                    <th>Change Time</th>
                    <th>Changed By</th>
                    <th>Change Made</th>
                    <th>More Info</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
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
<div id="bmaw_submission_quickedit_dialog" class="bmaw-sg">

    <div class="bmaw-sg-col1">
        <div class="bmaw-sg-col1-w1">
            <label for="meeting_name">Meeting Name</label>
            <input type="text" name="meeting_name" id="meeting_name">
        </div>

        <div class="bmaw-sg-col1-t1">
            <label for="start_time">Start Time</label>
            <input type="time" name="start_time" id="start_time" class="bmaw_submission_input_half">

        </div>
        <div class="bmaw-sg-col1-t2">
            <label for="weekday_tinyint">Weekday</label>
            <select name="weekday_tinyint" id="weekday_tinyint">
                <option value="1">Sunday</option>
                <option value="2">Monday</option>
                <option value="3">Tuesday</option>
                <option value="4">Wednesday</option>
                <option value="5">Thursday</option>
                <option value="6">Friday</option>
                <option value="7">Saturday</option>
            </select>
        </div>
        <div class="bmaw-sg-col1-t3">
            <label for="duration_hours">Duration</label>
            <select id="duration_hours">
                <option>0</option>
                <option selected="selected">1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
                <option>11</option>
                <option>12</option>
            </select> h
            <select id="duration_minutes">
                <option selected="selected">0</option>
                <option>5</option>
                <option>10</option>
                <option>15</option>
                <option>20</option>
                <option>25</option>
                <option>30</option>
                <option>35</option>
                <option>40</option>
                <option>45</option>
                <option>50</option>
                <option>55</option>
            </select> m
        </div>
        <div class="bmaw-sg-col1-w2">
            <label for="email_address">Email Address</label>
            <input type="text" name="email_address" id="email_address" required>
            <label for="virtual_meeting_link">Virtual Meeting Link</label>
            <input type="text" name="virtual_meeting_link" id="virtual_meeting_link" required>
        </div>
    </div>
    <div class="bmaw-grid-col2">
        <label for="location_text">Location</label>
        <input type="text" name="location_text" id="location_text" required>
        <label for="location_street">Street</label>
        <input type="text" name="location_street" id="location_street" required>
        <label for="location_info">Location Info</label>
        <input type="text" name="location_info" id="location_info" required>
        <label for="location_municipality">Municipality</label>
        <input type="text" name="location_municipality" id="location_municipality" required>
        <label for="location_province">Province</label>
        <input type="text" name="location_province" id="location_province" required>
        <label for="location_postal_code_1">Post Code</label>
        <input type="text" name="location_postal_code_1" id="location_postal_code_1" required>
    </div>

</div>
<!-- 
<div id="bmaw_submission_quickedit_dialog" class="hidden bmaw_submission_quickedit_dialog">
    <div class="bmaw-grid-col1">
    <div class="bmaw-grid-col1-wide">
        <label for="meeting_name">Meeting Name</label>
        <input type="text" name="meeting_name" id="meeting_name">
    </div>
        <div class="bmaw-col1-t1">
            <label for="start_time">Start Time</label>
            <input type="time" name="start_time" id="start_time" class="bmaw_submission_input_half">
        </div>
        <div class="bmaw-col1-t2">
            <!-- <input type="text" name="duration_time" id="duration_time" class="bmaw_submission_input_half" > 

            <label for="weekday_tinyint">Weekday</label>
            <select name="weekday_tinyint" id="weekday_tinyint">
                <option value="1">Sunday</option>
                <option value="2">Monday</option>
                <option value="3">Tuesday</option>
                <option value="4">Wednesday</option>
                <option value="5">Thursday</option>
                <option value="6">Friday</option>
                <option value="7">Saturday</option>
            </select>
        </div>
        <div class="bmaw-col1-t3">
            <label for="duration_hours">Duration</label>
            <select id="duration_hours">
                <option>0</option>
                <option selected="selected">1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
                <option>11</option>
                <option>12</option>
            </select> h
            <select id="duration_minutes">
                <option selected="selected">0</option>
                <option>5</option>
                <option>10</option>
                <option>15</option>
                <option>20</option>
                <option>25</option>
                <option>30</option>
                <option>35</option>
                <option>40</option>
                <option>45</option>
                <option>50</option>
                <option>55</option>
            </select> m
        </div>
        <div class="bmaw-grid-col1-wide2">

        <label for="email_address">Email Address</label>
        <input type="text" name="email_address" id="email_address" required>
        <label for="virtual_meeting_link">Virtual Meeting Link</label>
        <input type="text" name="virtual_meeting_link" id="virtual_meeting_link" required>
        </div>
    </div>
    <div class="bmaw-grid-col2">
        <label for="location_text">Location</label>
        <input type="text" name="location_text" id="location_text" required>
        <label for="location_street">Street</label>
        <input type="text" name="location_street" id="location_street" required>
        <label for="location_info">Location Info</label>
        <input type="text" name="location_info" id="location_info" required>
        <label for="location_municipality">Municipality</label>
        <input type="text" name="location_municipality" id="location_municipality" required>
        <label for="location_province">Province</label>
        <input type="text" name="location_province" id="location_province" required>
        <label for="location_postal_code_1">Post Code</label>
        <input type="text" name="location_postal_code_1" id="location_postal_code_1" required>
    </div>
</div>
 -->


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
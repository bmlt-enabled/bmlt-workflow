<?php

if (!defined('ABSPATH')) exit; // die if being called directly

wp_nonce_field('wp_rest', '_wprestnonce');

$bmlt_integration = new BMLTIntegration;

$meeting_counties_and_sub_provinces = $bmlt_integration->getMeetingCounties();

if ($meeting_counties_and_sub_provinces) {
    $counties = '<select class="meeting-input" name="quickedit_location_sub_province">';
    foreach ($meeting_counties_and_sub_provinces as $key) {
        $counties .= '<option value="' . $key . '">' . $key . '</option>';
    }
    $counties .= '</select>';
} else {
    $counties = <<<EOD
    <input class="meeting-input" type="text" name="quickedit_location_sub_province" size="50" id="quickedit_location_sub_province">
EOD;
}

$meeting_states_and_provinces = $bmlt_integration->getMeetingStates();

if ($meeting_states_and_provinces) {
    $states = '<select class="meeting-input" name="quickedit_location_province">';
    foreach ($meeting_states_and_provinces as $key) {
        $states .= '<option value="' . $key . '">' . $key . '</option>';
    }
    $states .= '</select>';
} else {
    $states = <<<EOD
    <input class="meeting-input" type="text" name="quickedit_location_province" size="50" id="quickedit_location_province" required>
EOD;
}

?>
<!-- Approve dialog -->
<div id="wbw_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="wbw_submission_approve_dialog_textarea">Approval note:</label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="wbw_submission_approve_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
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

<!-- Quickedit dialog -->
<div id="wbw_submission_quickedit_dialog" class="hidden">
    <div class="form-grid">
        <div class="form-grid-top">
            <div class="wbw_info_text">
                <br>Highlighted fields are from the user submission and your changes and will be stored when the QuickEdit is saved.
                <br><br>
            </div>
        </div>
        <div class="form-grid-col1">
            <label for="quickedit_meeting_name">Meeting Name</label>
            <input type="text" name="quickedit_meeting_name" id="quickedit_meeting_name" class="quickedit-input">
            <label for="quickedit_format_shared_id_list">Meeting Formats</label>
            <select class="quickedit-input" name="quickedit_format_shared_id_list" id="quickedit_format_shared_id_list" style="width: auto"></select>
            <div class="grid-flex-container">
                <div class="grid-flex-item">
                    <label for="quickedit_start_time">Start Time</label>
                    <input type="time" name="quickedit_start_time" id="quickedit_start_time" class="quickedit-input">

                </div>
                <div class="grid-flex-item">
                    <label for="quickedit_weekday_tinyint">Weekday</label>
                    <select class="quickedit-input" name="quickedit_weekday_tinyint" id="quickedit_weekday_tinyint">
                        <option value="1">Sunday</option>
                        <option value="2">Monday</option>
                        <option value="3">Tuesday</option>
                        <option value="4">Wednesday</option>
                        <option value="5">Thursday</option>
                        <option value="6">Friday</option>
                        <option value="7">Saturday</option>
                    </select>
                </div>
                    <div class="grid-flex-double">
                    <label for="quickedit_duration_hours">Duration</label>
                    <select class="quickedit-input" id="quickedit_duration_hours">
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
                    <select class="quickedit-input" id="quickedit_duration_minutes">
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
            </div>
            <label for="quickedit_virtual_meeting_link">Virtual Meeting Link</label>
            <input type="text" name="quickedit_virtual_meeting_link" id="quickedit_virtual_meeting_link" class="quickedit-input">
            <label for="quickedit_additional_info">Additional Information</label>
            <div class="grow-wrap">
                <textarea class="dialog_textarea" id="quickedit_additional_info" name="quickedit_additional_info" onInput="this.parentNode.dataset.replicatedValue = this.value" disabled></textarea>
            </div>
        </div>
        <div class="form-grid-col2">
            <label for="quickedit_location_text">Location</label>
            <input type="text" name="quickedit_quickedit_location_text" id="quickedit_location_text" class="quickedit-input">
            <label for="quickedit_location_street">Street</label>
            <input type="text" name="quickedit_location_street" id="quickedit_location_street" class="quickedit-input">
            <label for="quickedit_location_info">Location Info</label>
            <input type="text" name="quickedit_location_info" id="quickedit_location_info" class="quickedit-input">
            <label for="quickedit_location_municipality">Municipality</label>
            <input type="text" name="quickedit_location_municipality" id="quickedit_location_municipality" class="quickedit-input">
            <label for="quickedit_location_sub_province">Sub Province</label>
            <?php echo $counties ?>
            <label for="quickedit_location_province">State<span class="wbw-required-field"> *</span></label>
            <?php echo $states ?>
            <label for="quickedit_location_postal_code_1">Postcode<span class="wbw-required-field"> *</span></label>
            <input class="meeting-input" type="number" name="quickedit_location_postal_code_1" size="5" max="99999" id="quickedit_location_postal_code_1" required>
            <label for="quickedit_location_nation">Nation</label>
            <input class="meeting-input" type="text" name="quickedit_location_nation" size="50" id="quickedit_location_nation">
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
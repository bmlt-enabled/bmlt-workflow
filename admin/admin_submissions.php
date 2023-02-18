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


if ((!defined('ABSPATH') && (!defined('BMLTWF_RUNNING_UNDER_PHPUNIT')))) exit; // die if being called directly

use bmltwf\BMLTWF_Debug;
use bmltwf\BMLT\Integration;

wp_nonce_field('wp_rest', '_wprestnonce');

$bmlt_integration = new Integration();

$bmltwf_do_counties_and_sub_provinces = false;
$meeting_counties_and_sub_provinces = $bmlt_integration->getMeetingCounties();

if ($meeting_counties_and_sub_provinces) {
    $bmltwf_do_counties_and_sub_provinces = true;
}

$bmltwf_do_states_and_provinces = false;
$meeting_states_and_provinces = $bmlt_integration->getMeetingStates();

if ($meeting_states_and_provinces) {
    $bmltwf_do_states_and_provinces = true;
}

?>
<!-- Approve dialog -->
<div id="bmltwf_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_approve_dialog_textarea">Approval note:</label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_approve_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p>You can use the quickedit function to make any extra changes before approval.</p>
    <p>Are you sure you would like to approve the submission?</p>
</div>

<!-- Approve dialog -->
<div id="bmltwf_submission_approve_close_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_approve_close_dialog_textarea">Approval note:</label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_approve_close_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p>Choose whether you'd like the meeting to be deleted from BMLT, or marked as unpublished.</p>
    <input type='radio' name='close_action' id='close_unpublish'><label for='close_unpublish'>Unpublish</label>
    <input type='radio' name='close_action' id='close_delete'><label for='close_delete'>Delete</label>
</div>

<!-- Delete dialog -->
<div id="bmltwf_submission_delete_dialog" class="hidden" style="max-width:800px">
    <p>This change cannot be undone. Use this to remove an entirely unwanted submission from the list.</p>
    <p>Are you sure you would like to delete this submission completely?</p>
</div>

<!-- Reject dialog -->
<div id="bmltwf_submission_reject_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_reject_dialog_textarea">Rejection note:</label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_reject_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this reject for the submitter'></textarea>
    </div>
    <p>Are you sure you would like to reject this submission?</p>
</div>

<!-- Quickedit dialog -->
<div id="bmltwf_submission_quickedit_dialog" class="hidden">
    <hr class="bmltwf-quickedit-error-message"><br>

    <div class="form-grid">

        <div class="form-grid-top">

            <div class="bmltwf_info_text">
                <br>Highlighted fields are from the user submission and your changes and will be stored when the QuickEdit is saved.
                <br><br>
            </div>
        </div>
        <div class="form-grid-col1">
            <label for="quickedit_meeting_name">Meeting Name</label>
            <input type="text" name="quickedit_meeting_name" id="quickedit_meeting_name" class="quickedit-input">
            <label for="quickedit_format_shared_id_list">Meeting Formats
                <?php
                $req = get_option('bmltwf_required_meeting_formats') === 'true';
                if ($req) {
                    echo '<span class="bmltwf-required-field"> *</span>';
                }
                echo '</label>';
                echo '<select class="quickedit_format_shared_id_list-select2" name="quickedit_format_shared_id_list" id="quickedit_format_shared_id_list" style="width: auto"';
                if ($req) {
                    echo ' required';
                }
                echo '>';
                ?>
                </select>
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
                <label for="quickedit_virtual_meeting_additional_info">Virtual Meeting Additional Info</label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_virtual_meeting_additional_info" name="quickedit_virtual_meeting_additional_info" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_phone_meeting_number">Virtual Meeting Phone Details</label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_phone_meeting_number" name="quickedit_phone_meeting_number" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_virtual_meeting_link">Virtual Meeting Link</label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_virtual_meeting_link" name="quickedit_virtual_meeting_link" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_additional_info">Additional Information</label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_additional_info" name="quickedit_additional_info" onInput="this.parentNode.dataset.replicatedValue = this.value" disabled></textarea>
                </div>
        </div>
        <div class="form-grid-col2">
            <label for="quickedit_venue_type">Venue Type</label>
            <select name="quickedit_venue_type" id="quickedit_venue_type">
                <option value="" disabled selected hidden>Select one</option>
                <option value="1">Face to Face</option>
                <option value="2">Virtual only</option>
                <option value="3">Hybrid (Virtual and Face to Face)</option>
                <option value="4">Temporary Face to Face Closure</option>
            </select>
            <label for="quickedit_location_text">Location</label>
            <input type="text" name="quickedit_location_text" id="quickedit_location_text" class="quickedit-input">
            <label for="quickedit_location_street">Street</label>
            <input type="text" name="quickedit_location_street" id="quickedit_location_street" class="quickedit-input">
            <label for="quickedit_location_info">Location Info</label>
            <input type="text" name="quickedit_location_info" id="quickedit_location_info" class="quickedit-input">
            <label for="quickedit_location_municipality">City/Town/Suburb</label>
            <input type="text" name="quickedit_location_municipality" id="quickedit_location_municipality" class="quickedit-input">

            <div id="optional_location_sub_province">
                <label id="quickedit_location_sub_province_label" for="quickedit_location_sub_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_sub_province_displayname')) ?></label>
            </div>
            <div id="optional_location_province">
                <label id="quickedit_location_province_label" for="quickedit_location_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_province_displayname')) ?></label>
            </div>
            <label for="quickedit_location_postal_code_1"><?php echo sanitize_text_field(get_option('bmltwf_optional_postcode_displayname')) ?></label>
            <input class="quickedit-input" type="text" name="quickedit_location_postal_code_1" id="quickedit_location_postal_code_1" required>

            <div id="optional_location_nation">
                <label id="location_nation_label" for="quickedit_location_nation"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_nation_displayname')) ?></label>
                <input class="quickedit-input" type="text" name="quickedit_location_nation" size="50" id="quickedit_location_nation">
            </div>
        </div>
        <div class="form-grid-col3">
            <iframe id="quickedit_gmaps" width="100%" height="400" frameborder="0" style="border:0" referrerpolicy="no-referrer-when-downgrade" src="" allowfullscreen> </iframe>
            <div id="optional_auto_geocode_enabled"><br>
                <fieldset>
                    <legend>Meeting Geolocation (auto calculated)</legend>
                    <label for="quickedit_latitude">Latitude</label>
                    <input class="quickedit-input" type="number" name="quickedit_latitude" id="quickedit_latitude" disabled>
                    <label for="quickedit_longitude">Longitude</label>
                    <input class="quickedit-input" type="number" name="quickedit_longitude" id="quickedit_longitude" disabled>
                </fieldset>
            </div>
        </div>
    </div>
</div>


<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Meeting Submissions</h2>
    <hr class="bmltwf-error-message">

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
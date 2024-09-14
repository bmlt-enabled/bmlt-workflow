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

?>
<!-- Approve dialog -->
<div id="bmltwf_submission_approve_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_approve_dialog_textarea"><?php echo __( 'Approval note:', 'bmlt-workflow' ); ?></label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_approve_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p><?php echo __( 'You can use the quickedit function to make any extra changes before approval.', 'bmlt-workflow' ); ?></p>
    <p><?php echo __( 'Are you sure you would like to approve the submission?' ); ?></p>
</div>

<!-- Approve dialog -->
<div id="bmltwf_submission_approve_close_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_approve_close_dialog_textarea"><?php echo __( 'Approval note:', 'bmlt-workflow' ); ?></label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_approve_close_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this approval for the submitter'></textarea>
    </div>
    <p><?php echo __( "Choose whether you'd like the meeting to be deleted from BMLT, or marked as unpublished.", 'bmlt-workflow' ); ?></p>
    <input type='radio' name='close_action' id='close_unpublish'><label for='close_unpublish'><?php echo __( 'Unpublish', 'bmlt-workflow' ); ?></label>
    <input type='radio' name='close_action' id='close_delete'><label for='close_delete'><?php echo __( 'Delete', 'bmlt-workflow' ); ?></label>
</div>

<!-- Delete dialog -->
<div id="bmltwf_submission_delete_dialog" class="hidden" style="max-width:800px">
    <p><?php echo __( "This change cannot be undone. Use this to remove an entirely unwanted submission from the list.", 'bmlt-workflow' ); ?></p>
    <p><?php echo __( "Are you sure you would like to delete this submission completely?", 'bmlt-workflow' ); ?></p>
</div>

<!-- Reject dialog -->
<div id="bmltwf_submission_reject_dialog" class="hidden" style="max-width:800px">
    <label class='dialog_label' for="bmltwf_submission_reject_dialog_textarea">Rejection note:</label>
    <div class="grow-wrap">
        <textarea class='dialog_textarea' id="bmltwf_submission_reject_dialog_textarea" onInput="this.parentNode.dataset.replicatedValue = this.value" placeholder='Add a note to this reject for the submitter'></textarea>
    </div>
    <p><?php echo __( "Are you sure you would like to reject this submission?", 'bmlt-workflow' ); ?></p>
</div>

<!-- Quickedit dialog -->
<div id="bmltwf_submission_quickedit_dialog" class="hidden">
    <hr class="bmltwf-quickedit-error-message"><br>

    <div class="form-grid">

        <div class="form-grid-top">

            <div class="bmltwf_info_text">
                <br><?php echo __( "Highlighted fields are from the user submission and your changes and will be stored when the QuickEdit is saved.", 'bmlt-workflow' ); ?>
                <br><br>
            </div>
        </div>
        <div class="form-grid-col1">
            <label for="quickedit_meeting_name"><?php echo __( "Meeting Name", 'bmlt-workflow' ); ?></label>
            <input type="text" name="quickedit_meeting_name" id="quickedit_meeting_name" class="quickedit-input">
            <label for="quickedit_format_shared_id_list"><?php echo __( "Meeting Formats", 'bmlt-workflow' ); ?>
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
                        <label for="quickedit_start_time"><?php echo __( "Start Time", 'bmlt-workflow' ); ?></label>
                        <input type="time" name="quickedit_start_time" id="quickedit_start_time" class="quickedit-input">

                    </div>
                    <div class="grid-flex-item">
                        <label for="quickedit_weekday_tinyint"><?php echo __( "Weekday", 'bmlt-workflow' ); ?></label>
                        <select class="quickedit-input" name="quickedit_weekday_tinyint" id="quickedit_weekday_tinyint">
                            <option value="1"><?php echo __( "Sunday", 'bmlt-workflow' ); ?></option>
                            <option value="2"><?php echo __( "Monday", 'bmlt-workflow' ); ?></option>
                            <option value="3"><?php echo __( "Tuesday", 'bmlt-workflow' ); ?></option>
                            <option value="4"><?php echo __( "Wednesday", 'bmlt-workflow' ); ?></option>
                            <option value="5"><?php echo __( "Thursday", 'bmlt-workflow' ); ?></option>
                            <option value="6"><?php echo __( "Friday", 'bmlt-workflow' ); ?></option>
                            <option value="7"><?php echo __( "Saturday", 'bmlt-workflow' ); ?></option>
                        </select>
                    </div>
                    <div class="grid-flex-double">
                        <label for="quickedit_duration_hours"><?php echo __( "Duration", 'bmlt-workflow' ); ?></label>
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
                <label for="quickedit_virtual_meeting_additional_info"><?php echo __( "Virtual Meeting Additional Info", 'bmlt-workflow' ); ?></label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_virtual_meeting_additional_info" name="quickedit_virtual_meeting_additional_info" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_phone_meeting_number"><?php echo __( "Virtual Meeting Phone Details", 'bmlt-workflow' ); ?></label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_phone_meeting_number" name="quickedit_phone_meeting_number" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_virtual_meeting_link"><?php echo __( "Virtual Meeting Link", 'bmlt-workflow' ); ?></label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_virtual_meeting_link" name="quickedit_virtual_meeting_link" onInput="this.parentNode.dataset.replicatedValue = this.value"></textarea>
                </div>
                <label for="quickedit_additional_info"><?php echo __( "Additional Information", 'bmlt-workflow' ); ?></label>
                <div class="grow-wrap">
                    <textarea class="dialog_textarea quickedit-input" id="quickedit_additional_info" name="quickedit_additional_info" onInput="this.parentNode.dataset.replicatedValue = this.value" disabled></textarea>
                </div>
        </div>
        <div class="form-grid-col2">
            <label for="quickedit_venue_type"><?php echo __( "Venue Type", 'bmlt-workflow' ); ?></label>
            <select name="quickedit_venue_type" id="quickedit_venue_type" class="quickedit-input">
                <option value="" disabled selected hidden><?php echo __( "Select one", 'bmlt-workflow' ); ?></option>
                <option value="1"><?php echo __( "Face to Face", 'bmlt-workflow' ); ?></option>
                <option value="2"><?php echo __( "Virtual only", 'bmlt-workflow' ); ?></option>
                <option value="3"><?php echo __( "Hybrid (Virtual and Face to Face)", 'bmlt-workflow' ); ?></option>
                <option value="4"><?php echo __( "Temporarily Virtual Meeting", 'bmlt-workflow' ); ?></option>
            </select>
            <label for="quickedit_published"><?php echo __( 'Meeting Published?', 'bmlt-workflow' ); ?></label>
            <select class="quickedit-input" name="quickedit_published" id="quickedit_published">
                <option value="1"><?php echo __( 'Yes', 'bmlt-workflow' ); ?></option>
                <option value="0"><?php echo __( 'No', 'bmlt-workflow' ); ?></option>
            </select>
            <div id='optional_virtualna_published'>
                <label for="quickedit_virtualna_published"><?php echo __( 'Publish on virtual.na.org?', 'bmlt-workflow' ); ?></label>
                <select class="quickedit-input" name="quickedit_virtualna_published" id="quickedit_virtualna_published">
                    <option value="1"><?php echo __( 'Yes', 'bmlt-workflow' ); ?></option>
                    <option value="0"><?php echo __( 'No', 'bmlt-workflow' ); ?></option>
                </select>
            </div>
            <label for="quickedit_location_text"><?php echo __( "Location", 'bmlt-workflow' ); ?></label>
            <input type="text" name="quickedit_location_text" id="quickedit_location_text" class="quickedit-input">
            <label for="quickedit_location_street"><?php echo __( "Street", 'bmlt-workflow' ); ?></label>
            <input type="text" name="quickedit_location_street" id="quickedit_location_street" class="quickedit-input">
            <label for="quickedit_location_info"><?php echo __( "Location Info", 'bmlt-workflow' ); ?></label>
            <input type="text" name="quickedit_location_info" id="quickedit_location_info" class="quickedit-input">
            <label for="quickedit_location_municipality"><?php echo __( "City/Town/Suburb", 'bmlt-workflow' ); ?></label>
            <input type="text" name="quickedit_location_municipality" id="quickedit_location_municipality" class="quickedit-input">

            <div id="optional_location_sub_province">
                <label id="quickedit_location_sub_province_label" for="quickedit_location_sub_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_sub_province_displayname')) ?></label>
            </div>
            <div id="optional_location_province">
                <label id="quickedit_location_province_label" for="quickedit_location_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_province_displayname')) ?></label>
            </div>
            <div id="optional_postcode">
                <label id="quickedit_location_postal_code_1_label" for="quickedit_location_postal_code_1"><?php echo sanitize_text_field(get_option('bmltwf_optional_postcode_displayname')) ?></label>
                <input class="quickedit-input" type="text" name="quickedit_location_postal_code_1" id="quickedit_location_postal_code_1" required>
            </div>
            <div id="optional_location_nation">
                <label id="location_nation_label" for="quickedit_location_nation"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_nation_displayname')) ?></label>
                <input class="quickedit-input" type="text" name="quickedit_location_nation" size="50" id="quickedit_location_nation">
            </div>
        </div>
        <div class="form-grid-col3">
            <div id="bmltwf_quickedit_map"></div>
            <div id="optional_auto_geocode_enabled"><br>
                <fieldset>
                    <legend><?php echo __( "Meeting Geolocation (auto calculated)", 'bmlt-workflow' ); ?></legend>
                    <label for="quickedit_latitude"><?php echo __( "Latitude", 'bmlt-workflow' ); ?></label>
                    <input class="quickedit-input" type="number" name="quickedit_latitude" id="quickedit_latitude" disabled>
                    <label for="quickedit_longitude"><?php echo __( "Longitude", 'bmlt-workflow' ); ?></label>
                    <input class="quickedit-input" type="number" name="quickedit_longitude" id="quickedit_longitude" disabled>
                </fieldset>
            </div>
        </div>
    </div>
</div>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2><?php echo __( "Meeting Submissions", 'bmlt-workflow' ); ?></h2>
    <hr class="bmltwf-error-message">

    <div class="dt-container" style="display: none;">
        <table id="dt-submission" class="display" style="width:90%">
            <thead>
                <tr>
                    <th><?php echo __( "ID", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submitter Name", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submitter Email", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Service Body", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Summary", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submission Time", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Time", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Changed By", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submission Status", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Made", 'bmlt-workflow' ); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php echo __( "ID", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submitter Name", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submitter Email", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Service Body", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Summary", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submission Time", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Time", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Changed By", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Submission Status", 'bmlt-workflow' ); ?></th>
                    <th><?php echo __( "Change Made", 'bmlt-workflow' ); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
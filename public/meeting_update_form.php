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


use bmltwf\BMLT\Integration;

$bmltwf_bmlt_test_status = get_option('bmltwf_bmlt_test_status', "failure");
if ($bmltwf_bmlt_test_status != "success") {
    wp_die("<h4>BMLTWF Plugin Error: BMLT Root Server not configured and tested.</h4>");
}

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

<div id="form_replace" class="bmltwf_wide_form">
    <form action="#" method="post" id="meeting_update_form">
        <div id="meeting_update_form_header">
            <div>
                <label for="update_reason">Reason For Update:</label>
                <select class="update-form-select" name="update_reason" id="update_reason">
                    <option disabled="null" selected="null">Select Reason...</option>
                    <option value="reason_new">New Meeting</option>
                    <option value="reason_change">Change Existing Meeting</option>
                    <option value="reason_close">Close Meeting</option>
                </select>
            </div>
            <div id="meeting_selector">
                <br>
                <label for="meeting-searcher">Search For Meeting:</label>
                <br>
                <select name="meeting-searcher" class="meeting-searcher" id="meeting-searcher">
                    <option></option>
                </select>
                <input type="hidden" name="meeting_id" id="meeting_id" value="">
            </div>
        </div>
        <div id="meeting_content" class="form-grid">
            <div class="form-grid-top">
                <p id="instructions"></p>
            </div>

            <!-- meeting details -->
            <div id="meeting_details" class="form-grid-col1">
                <fieldset>
                    <legend>Meeting Details</legend>

                    <div class="form-grid-col1">
                        <label for="meeting_name">Group Name<span class="bmltwf-required-field"> *</span></label>
                        <input class="meeting-input" type="text" name="meeting_name" size="50" id="meeting_name" required>
                        <label for="weekday_tinyint">Meeting Day:<span class="bmltwf-required-field"> *</span></label>
                        <select class="meeting-input" name="weekday_tinyint" id="weekday_tinyint">
                            <option value=1>Sunday</option>
                            <option value=2>Monday</option>
                            <option value=3>Tuesday</option>
                            <option value=4>Wednesday</option>
                            <option value=5>Thursday</option>
                            <option value=6>Friday</option>
                            <option value=7>Saturday</option>
                        </select>
                        <div class="grid-flex-container">
                            <div class="grid-flex-item">
                                <label for="start_time">Start Time<span class="bmltwf-required-field"> *</span></label>
                                <input class="meeting-input" type="time" name="start_time" size="10" id="start_time" required>
                            </div>
                            <div class="grid-flex-item">
                                <label>Duration</label>
                                <div class="inline">
                                    <span>
                                        <select class="meeting-input" id="duration_hours">
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
                                        </select>
                                        <label for="duration_hours">H</label>
                                    </span>
                                    <span>
                                        <select class="meeting-input" id="duration_minutes">
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
                                        </select>
                                        <label for="duration_minutes">M</label>
                                    </span>
                                </div>
                            </div>

                        </div>
                        <input type="hidden" name="duration_time" size="10" id="duration_time" required>

                        <label for="display_format_shared_id_list">Meeting Formats<span class="bmltwf-required-field"> *</span></label>
                        <select class="display_format_shared_id_list-select2" name="display_format_shared_id_list" id="display_format_shared_id_list" required></select>
                        <input type="hidden" name="format_shared_id_list" id="format_shared_id_list">
                        <div id="location_fields">
                            <label for="location_text">Location (eg: a building name)<span class="bmltwf-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_text" size="50" id="location_text" required>
                            <label for="location_street">Street Address<span class="bmltwf-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_street" size="50" id="location_street" required>
                            <label for="location_info">Extra Location Info (eg: Near the park)</label>
                            <input class="meeting-input" type="text" name="location_info" size="50" id="location_info">
                        </div>
                        <label for="location_municipality">City/Town/Suburb<span class="bmltwf-required-field"> *</span></label>
                        <input class="meeting-input" type="text" name="location_municipality" size="50" id="location_municipality" required>
                        <div id="optional_location_sub_province">
                            <label id="location_sub_province_label" for="location_sub_province"><?php sanitize_text_field(get_option('bmltwf_optional_location_sub_province_displayname'))?></label>

                            <?php
                            if ($bmltwf_do_counties_and_sub_provinces) {
                                echo '<select class="meeting-input" id="location_sub_province" name="location_sub_province">';
                                foreach ($meeting_counties_and_sub_provinces as $key) {
                                    echo '<option value="' . $key . '">' . $key . '</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<input class="meeting-input" type="text" name="location_sub_province" size="50" id="location_sub_province">';
                            }

                            ?>

                        </div>
                        <div id="optional_location_province">
                            <label id="location_province_label" for="location_province"><?php sanitize_text_field(get_option('bmltwf_optional_location_province_displayname'))?></label>

                            <?php
                            if ($bmltwf_do_states_and_provinces) {
                                echo '<select class="meeting-input" id="location_province" name="location_province">';
                                foreach ($meeting_states_and_provinces as $key) {
                                    echo '<option value="' . $key . '">' . $key . '</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<input class="meeting-input" type="text" name="location_province" size="50" id="location_province" required>';
                            }

                            ?>

                        </div>
                        <div id="optional_postcode">
                            <label for="location_postal_code_1"><?php sanitize_text_field(get_option('bmltwf_optional_postcode_displayname'))?></label>
                            <input class="meeting-input" type="text" name="location_postal_code_1" id="location_postal_code_1" required>
                        </div>
                        <div id="optional_location_nation">
                            <label id="location_nation_label" for="location_nation"><?php sanitize_text_field(get_option('bmltwf_optional_location_nation_displayname'))?></label>
                            <input class="meeting-input" type="text" name="location_nation" size="50" id="location_nation">
                        </div>
                        <div class="tooltip" tabindex="0">
                            <label for="service_body_bigint">Service Committee
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="right">
                                Creating a new meeting and unsure of your service committee?
                                <br>Pick the closest match and leave us a note in the 'Any Other Comments' section below
                                <i></i>
                            </div>
                        </div>
                        <select class="meeting-input" name="service_body_bigint" id="service_body_bigint">
                            <option value="" disabled selected hidden>Select one</option>
                        </select>


                    </div>
                </fieldset>
            </div>

            <!-- virtual meeting settings -->

            <div id="virtual_meeting_options" class="form-grid-col2-1">
                <fieldset>
                    <legend>Virtual Meeting Options</legend>
                    <label for="virtual_hybrid_select">Is this a virtual, hybrid or temporarily closed in person meeting?</label>
                    <select name="virtual_hybrid_select" id="virtual_hybrid_select">
                        <option value="" disabled selected hidden>Select one</option>
                        <option value="none">No</option>
                        <option value="virtual">Yes - Virtual only</option>
                        <option value="hybrid"">Yes - Hybrid (Virtual and Face to Face)</option>
                            <option value=" tempclosure"">Yes -Temporary Face to Face Closure</option>
                    </select>
                    <div id="virtual_meeting_settings">
                        <div class="tooltip" tabindex="0">
                            <label for="virtual_meeting_link">Online Meeting Link
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="left">
                                A URL for the virtual meeting eg:
                                <br>https://zoom.us/j/123456789?pwd=FxL3NlWVFId0l1cWh1
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="url" name="virtual_meeting_link" maxlength="128" size="128" id="virtual_meeting_link"></textarea>
                        <div class="tooltip" tabindex="0">
                            <label for="virtual_meeting_additional_info">Virtual Meeting Additional Info
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="left">
                                Additional information, such as a meeting ID and Password eg:
                                <br>Zoom ID: 456 033 8613, Passcode: 1953
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="text" name="virtual_meeting_additional_info" maxlength="128" size="128" id="virtual_meeting_additional_info"></textarea>
                        <div class="tooltip" tabindex="0">
                            <label for="phone_meeting_number">Phone Meeting Dial-in Number
                                <span class="dashicons dashicons-info-outline" style="color: cornflowerblue;"></span>
                            </label>

                            <div class="left">
                                Any phone dialin details for this virtual meeting.
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="text" name="phone_meeting_number" maxlength="128" size="128" id="phone_meeting_number"></textarea>
                    </div>
                </fieldset>
            </div>

            <!-- personal details -->
            <div id="personal_details" class="form-grid-col2-2">
                <fieldset>
                    <legend>Personal Details</legend>
                    <label for="first_name">First Name<span class="bmltwf-required-field">*</span></label>
                    <input type="text" name="first_name" size="20" id="first_name" required>
                    <label for="last_name">Last Name<span class="bmltwf-required-field">*</span></label>
                    <input type="text" name="last_name" size="20" id="last_name" required>
                    <label for="email_address">Email Address<span class="bmltwf-required-field">*</span></label>
                    <input type="email" name="email_address" id="email_address" size="50" required>
                    <label for="add_email" class="add_email">Add this email as a contact
                        address for the group</label>
                    <select name="add_email" id="add_email">
                        <option value="yes">Yes</option>
                        <option value="no" selected>No</option>
                    </select>
                    <label for="contact_number_confidential">Contact Number (Confidential)</label>
                    <input type="number" name="contact_number_confidential" id="contact_number_confidential">
                    <label for="group_relationship">Relationship to group<span class="bmltwf-required-field">*</span></label>
                    <select name="group_relationship" id="group_relationship" required>
                        <option value="" disabled selected hidden>Select one</option>
                        <option value="Group Member">Group Member</option>
                        <option value="Area Trusted Servant">Area Trusted Servant</option>
                        <option value="Regional Trusted Servant">Regional Trusted Servant</option>
                        <option value="NA Member">NA Member</option>
                        <option value="Not A Member">Not A Member</option>
                    </select>
                </fieldset>
            </div>


            <!-- other details -->
            <div class="form-grid-bottom">
                <div id="additional_info_div">
                    <fieldset>
                        <legend>Additional Information</legend>
                        <label for="additional_info">Any Other Comments</label>
                        <textarea name="additional_info" id="additional_info" maxlength="512" rows="5" cols="50" placeholder="Provide any more detail that may help us action your meeting change request"></textarea>
                        <div id="starter_pack">
                            <label for="starter_kit_required">Starter Kit Required</label>
                            <select name="starter_kit_required" id="starter_kit_required">
                                <option value="yes" selected="true" id="starter_kit_required_yes">Yes</option>
                                <option value="no" id="starter_kit_required_no">No</option>
                            </select>
                            <div id="starter_kit_postal_address_div">
                                <label for="starter_kit_postal_address">Starter Kit Postal Address<span class="bmltwf-required-field"> *</span></label>
                                <textarea name="starter_kit_postal_address" id="starter_kit_postal_address" maxlength="512" rows="5" cols="50"></textarea>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <hr class="bmltwf-error-message">

                <br><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"><span class="spinner" id="bmltwf-submit-spinner"></span>
            </div>
        </div>

    </form>
</div>
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
    wp_die("<h4><?php echo __( 'BMLTWF Plugin Error: BMLT Root Server not configured and tested', 'bmlt-workflow' ); ?>.</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>

<div id="form_replace" class="bmltwf_wide_form">
    <form action="#" method="post" id="meeting_update_form">
        <div id="meeting_update_form_header">
            <div>
                <label for="update_reason"><?php echo __( 'Reason For Update', 'bmlt-workflow' ); ?>:</label>
                <select class="update-form-select" name="update_reason" id="update_reason">
                    <option disabled="null" selected="null"><?php echo __( 'Select Reason', 'bmlt-workflow' ); ?>...</option>
                    <option value="reason_new"><?php echo __( 'New Meeting', 'bmlt-workflow' ); ?></option>
                    <option value="reason_change"><?php echo __( 'Change Existing Meeting (including Temporary Closure)', 'bmlt-workflow' ); ?></option>
                    <option value="reason_close"><?php echo __( 'Permanently Close Meeting', 'bmlt-workflow' ); ?></option>
                </select>
            </div>
            <div id="meeting_selector">
                <br>
                <label for="meeting-searcher"><?php echo __( 'Search For Meeting', 'bmlt-workflow' ); ?>:</label>
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
                    <legend><?php echo __( 'Meeting Details', 'bmlt-workflow' ); ?></legend>

                    <div class="form-grid-col1">

                        <label for="meeting_name"><?php echo __( 'Group Name', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                        <input class="meeting-input" type="text" name="meeting_name" size="50" id="meeting_name" required>
                        <label for="weekday_tinyint"><?php echo __( 'Meeting Day', 'bmlt-workflow' ); ?>:<span class="bmltwf-required-field"> *</span></label>
                        <select class="meeting-input" name="weekday_tinyint" id="weekday_tinyint">
                            <option value=1><?php echo __( 'Sunday', 'bmlt-workflow' ); ?></option>
                            <option value=2><?php echo __( 'Monday', 'bmlt-workflow' ); ?></option>
                            <option value=3><?php echo __( 'Tuesday', 'bmlt-workflow' ); ?></option>
                            <option value=4><?php echo __( 'Wednesday', 'bmlt-workflow' ); ?></option>
                            <option value=5><?php echo __( 'Thursday', 'bmlt-workflow' ); ?></option>
                            <option value=6><?php echo __( 'Friday', 'bmlt-workflow' ); ?></option>
                            <option value=7><?php echo __( 'Saturday', 'bmlt-workflow' ); ?></option>
                        </select>
                        <div class="grid-flex-container">
                            <div class="grid-flex-item">
                                <label for="start_time"><?php echo __( 'Start Time', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                                <input class="meeting-input" type="time" name="start_time" size="10" id="start_time" required>
                            </div>
                            <div class="grid-flex-item">
                                <label><?php echo __( 'Duration', 'bmlt-workflow' ); ?></label>
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

                        <label id="display_format_shared_id_list_label" for="display_format_shared_id_list"><?php echo __( 'Meeting Formats', 'bmlt-workflow' ); ?>
                            <?php
                            $req = get_option('bmltwf_required_meeting_formats') === 'true';
                            if ($req) {
                                echo '<span class="bmltwf-required-field"> *</span>';
                            }?>
                        </label>

                        <?php echo '<select class="display_format_shared_id_list-select2" name="display_format_shared_id_list" id="display_format_shared_id_list"';
                        if ($req) {
                            echo ' required';
                        }
                        echo '>';
                        ?>
                        </select>
                        <input type="hidden" name="format_shared_id_list" id="format_shared_id_list">
                        <label for="venue_type"><?php echo __( 'Is this a virtual, hybrid or temporarily virtual in person meeting?', 'bmlt-workflow' ); ?></label>
                        <select class="meeting-input" name="venue_type" id="venue_type" required>
                            <option value="" disabled selected hidden><?php echo __( 'Select one', 'bmlt-workflow' ); ?></option>
                            <option value="1"><?php echo __( 'No - Standard Face to Face meeting', 'bmlt-workflow' ); ?></option>
                            <option value="2"><?php echo __( 'Yes - Virtual only', 'bmlt-workflow' ); ?></option>
                            <option value="3"><?php echo __( 'Yes - Hybrid (Virtual and Face to Face)', 'bmlt-workflow' ); ?></option>
                            <option value="4"><?php echo __( 'Yes - Temporarily Virtual (venue closed but meeting is still running virtually)', 'bmlt-workflow' ); ?></option>
                        </select>
                        <div id="publish_div">
                            <div class="bmltwf_tooltip" tabindex="0">
                                <label for="published"><?php echo __( 'Temporary Meeting Closures - Is this meeting published in the public meeting list?', 'bmlt-workflow' ); ?>
                                    <span class="dashicons dashicons-info-outline"></span>
                                </label>
                                <div class="bmltwf_right">
                                    <?php echo __( 'You can use this option to temporarily hide your meeting, such as a temporary venue closure,', 'bmlt-workflow' ); ?>
                                    <br><?php echo __('or to reopen your meeting after it has been temporarily closed'); ?>
                                </div>
                            </div>
                            <select class="meeting-input" name="published" id="published">
                                <option value="1"><?php echo __( 'Yes - Meeting will be shown in search', 'bmlt-workflow' ); ?></option>
                                <option value="0"><?php echo __( 'No - Meeting will not be shown in search', 'bmlt-workflow' ); ?></option>
                            </select>
                        </div>
                        <div id="location_fields">
                            <label for="location_text"><?php echo __( 'Location (eg: a building name)', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_text" size="50" id="location_text" required>
                            <label for="location_street"><?php echo __( 'Street Address', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_street" size="50" id="location_street" required>
                            <label for="location_info"><?php echo __( 'Extra Location Info (eg: Near the park)', 'bmlt-workflow' ); ?></label>
                            <input class="meeting-input" type="text" name="location_info" size="50" id="location_info">
                        </div>
                        <label for="location_municipality"><?php echo __( 'City/Town/Suburb', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                        <input class="meeting-input" type="text" name="location_municipality" size="50" id="location_municipality" required>
                        <div id="optional_location_sub_province">
                            <label id="location_sub_province_label" for="location_sub_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_sub_province_displayname')) ?></label>
                        </div>
                        <div id="optional_location_province">
                            <label id="location_province_label" for="location_province"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_province_displayname')) ?></label>
                        </div>
                        <div id="optional_postcode">
                            <label id="location_postal_code_1_label" for="location_postal_code_1"><?php echo sanitize_text_field(get_option('bmltwf_optional_postcode_displayname')) ?></label>
                            <input class="meeting-input" type="text" name="location_postal_code_1" id="location_postal_code_1" required>
                        </div>
                        <div id="optional_location_nation">
                            <label id="location_nation_label" for="location_nation"><?php echo sanitize_text_field(get_option('bmltwf_optional_location_nation_displayname')) ?></label>
                            <input class="meeting-input" type="text" name="location_nation" size="50" id="location_nation">
                        </div>
                        <div class="bmltwf_tooltip" tabindex="0">
                            <label for="service_body_bigint"><?php echo __( 'Service Body', 'bmlt-workflow' ); ?>
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="bmltwf_right">
                            <?php echo __( 'Creating a new meeting and unsure of your service body?', 'bmlt-workflow' ); ?>
                                <br><?php echo __( "Pick the closest match and leave us a note in the 'Any Other Comments' section below", 'bmlt-workflow' ); ?>

                                <i></i>
                            </div>
                        </div>
                        <select class="meeting-input" name="service_body_bigint" id="service_body_bigint">
                            <option value="" disabled selected hidden><?php echo __( 'Select one', 'bmlt-workflow' ); ?></option>
                        </select>
                    </div>
                </fieldset>
            </div>

            <!-- virtual meeting settings -->

            <div id="virtual_meeting_options" class="form-grid-col2-2">
                <fieldset>
                    <legend><?php echo __( 'Virtual Meeting Options', 'bmlt-workflow' ); ?></legend>
                    <div id="virtual_meeting_settings">
                        <div class="bmltwf_tooltip" tabindex="0">
                            <label for="virtual_meeting_link"><?php echo __( 'Virtual Meeting Link', 'bmlt-workflow' ); ?>
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="bmltwf_left">
                                <?php echo __( 'A URL for the virtual meeting eg:', 'bmlt-workflow' ); ?>
                                <br>https://zoom.us/j/123456789?pwd=FxL3NlWVFId0l1cWh1
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="url" name="virtual_meeting_link" maxlength="128" size="128" id="virtual_meeting_link"></textarea>
                        <div class="bmltwf_tooltip" tabindex="0">
                            <label for="virtual_meeting_additional_info"><?php echo __( 'Virtual Meeting Additional Info', 'bmlt-workflow' ); ?>
                                <span class="dashicons dashicons-info-outline"></span>
                            </label>
                            <div class="bmltwf_left">
                            <?php echo __( 'Additional information, such as a meeting ID and Password eg:', 'bmlt-workflow' ); ?>
                                <br>Zoom ID: 456 033 8613, Passcode: 1953
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="text" name="virtual_meeting_additional_info" maxlength="128" size="128" id="virtual_meeting_additional_info"></textarea>
                        <div class="bmltwf_tooltip" tabindex="0">
                            <label for="phone_meeting_number"><?php echo __( 'Phone Meeting Dial-in Number', 'bmlt-workflow' ); ?>
                                <span class="dashicons dashicons-info-outline" style="color: cornflowerblue;"></span>
                            </label>

                            <div class="bmltwf_left">
                            <?php echo __( 'Any phone dialin details for this virtual meeting', 'bmlt-workflow' ); ?>.
                                <i></i>
                            </div>
                        </div>
                        <textarea class="meeting-input" type="text" name="phone_meeting_number" maxlength="128" size="128" id="phone_meeting_number"></textarea>
                        <div id="virtualna_publish_div">
                            <div class="bmltwf_tooltip" tabindex="0">
                                <label for="virtualna_published"><?php echo __( 'virtual.na.org - Is this meeting published in the global online meeting list?', 'bmlt-workflow' ); ?>
                                    <span class="dashicons dashicons-info-outline"></span>
                                </label>
                                <div class="bmltwf_right">
                                    <?php echo __( 'You can use this option to publish your virtual meeting', 'bmlt-workflow' ); ?>
                                    <br><?php echo __('from the online meeting list at virtualna.org'); ?>
                                </div>
                            </div>
                            <select class="meeting-input" name="virtualna_published" id="virtualna_published">
                                <option value="1"><?php echo __( 'Yes - Meeting will be shown in the virtual.na.org list', 'bmlt-workflow' ); ?></option>
                                <option value="0"><?php echo __( 'No - Meeting will not be shown in the virtual.na.org list', 'bmlt-workflow' ); ?></option>
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>
            <input type="hidden" name="temporarilyVirtual" id="temporarilyVirtual" value="false">

            <!-- personal details -->
            <div id="personal_details" class="form-grid-col2-1">
                <fieldset>
                    <legend><?php echo __( 'Personal Details (Confidential)', 'bmlt-workflow' ); ?></legend>
                    <label for="first_name"><?php echo __( 'First Name', 'bmlt-workflow' ); ?><span class="bmltwf-required-field">*</span></label>
                    <input type="text" name="first_name" size="20" id="first_name" required>
                    <label for="last_name"><?php echo __( 'Last Initial', 'bmlt-workflow' ); ?><span class="bmltwf-required-field">*</span></label>
                    <input type="text" name="last_name" size="20" id="last_name" required>
                    <label for="email_address"><?php echo __( 'Email Address', 'bmlt-workflow' ); ?><span class="bmltwf-required-field">*</span></label>
                    <input type="email" name="email_address" id="email_address" size="50" required>
                    <label for="add_contact" class="add_contact"><?php echo __( 'Add your details as a contact for the group', 'bmlt-workflow' ); ?></label>
                    <select name="add_contact" id="add_contact">
                        <option value="yes"><?php echo __( 'Yes', 'bmlt-workflow' ); ?></option>
                        <option value="no" selected><?php echo __( 'No', 'bmlt-workflow' ); ?></option>
                    </select>
                    <label for="contact_number"><?php echo __( 'Contact Number', 'bmlt-workflow' ); ?></label>
                    <input type="tel" name="contact_number" id="contact_number">
                    <label for="group_relationship"><?php echo __( 'Relationship to group', 'bmlt-workflow' ); ?><span class="bmltwf-required-field">*</span></label>
                    <select name="group_relationship" id="group_relationship" required>
                        <option value="" disabled selected hidden><?php echo __( 'Select one', 'bmlt-workflow' ); ?></option>
                        <option value="Group Member"><?php echo __( 'Group Member', 'bmlt-workflow' ); ?></option>
                        <option value="Area Trusted Servant"><?php echo __( 'Area Trusted Servant', 'bmlt-workflow' ); ?></option>
                        <option value="Regional Trusted Servant"><?php echo __( 'Regional Trusted Servant', 'bmlt-workflow' ); ?></option>
                        <option value="NA Member"><?php echo __( 'NA Member', 'bmlt-workflow' ); ?></option>
                        <option value="Not A Member"><?php echo __( 'Not A Member', 'bmlt-workflow' ); ?></option>
                    </select>
                </fieldset>
            </div>


            <!-- other details -->
            <div class="form-grid-bottom">
                <div id="additional_info_div">
                    <fieldset>
                        <legend><?php echo __( 'Additional Information', 'bmlt-workflow' ); ?></legend>
                        <label for="additional_info"><?php echo __( 'Any Other Comments', 'bmlt-workflow' ); ?></label>
                        <textarea name="additional_info" id="additional_info" maxlength="512" rows="5" cols="50" placeholder="<?php echo __( 'Provide any more detail that may help us action your meeting change request', 'bmlt-workflow' ); ?>"></textarea>
                        <div id="starter_pack">
                            <label for="starter_kit_required"><?php echo __( 'Starter Kit Required', 'bmlt-workflow' ); ?></label>
                            <select name="starter_kit_required" id="starter_kit_required">
                                <option value="yes" selected="true" id="starter_kit_required_yes"><?php echo __( 'Yes', 'bmlt-workflow' ); ?></option>
                                <option value="no" id="starter_kit_required_no"><?php echo __( 'No', 'bmlt-workflow' ); ?></option>
                            </select>
                            <div id="starter_kit_postal_address_div">
                                <label for="starter_kit_postal_address"><?php echo __( 'Starter Kit Postal Address', 'bmlt-workflow' ); ?><span class="bmltwf-required-field"> *</span></label>
                                <textarea name="starter_kit_postal_address" id="starter_kit_postal_address" maxlength="512" rows="5" cols="50"></textarea>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <hr class="bmltwf-error-message">

                <br>
                <button class="button" type="submit" name="submit" id="submit">
                    <span id="bmltwf-submit-spinner" class="button"><?php echo __( 'Submit Form', 'bmlt-workflow' ); ?></span>
                </button>
            </div>
        </div>

    </form>
</div>
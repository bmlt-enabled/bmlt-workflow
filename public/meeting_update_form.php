<?php

if (!class_exists('BMLTIntegration')) {
    require_once(WBW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

$wbw_bmlt_test_status = get_option('wbw_bmlt_test_status', "failure");
if ($wbw_bmlt_test_status != "success") {
    wp_die("<h4>WBW Plugin Error: BMLT Server not configured and tested.</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

?>
<div id="form_replace">
    <form action="#" method="post" id="meeting_update_form">
        <input type="hidden" name="action" value="meeting_update_form_response">
        <div class="rendered-form">
            <div>
                <label for="update_reason"">Reason For Update</label>
                <select name=" update_reason" id="update_reason">
                    <option disabled="null" selected="null">Select Reason...</option>
                    <option value="reason_new">New Meeting</option>
                    <option value="reason_change">Change Existing Meeting</option>
                    <option value="reason_close">Close Meeting</option>
                    <option value="reason_other">Other</option>
                    </select>
            </div>
            <div id="other_reason_div">
                <label for="other_reason">Other Reason</label>
                <textarea name="other_reason" id="other_reason" rows="5" cols="50" placeholder="Provide as much detail about your meeting change request as you can and we'll endeavour to help"></textarea>
            </div>
            <div id="meeting_selector">
                <br>
                <label for="update_reason"">Search For Meeting</label>
                <select class="meeting-searcher" id="meeting-searcher">
                    <option></option>
                </select>
                <br><br>
                <input type="hidden" name="meeting_id" id="meeting_id" value="">
            </div>
            <div id="meeting_content" class="form-grid">
                <p id="reason_change_text" style="display: none;">We've retrieved the details below from our system. Please make any changes and then submit your update.
                <p id="reason_other_text" style="display: none;">Please let us know the details about your meeting change.
                <p id="reason_new_text" style="display: none;">Please fill in the details of your new meeting, and whether your new meeting needs a starter kit provided, and then submit your update. Note: If your meeting meets multiple times a week, please submit additional new meeting requests for each day you meet.
                <p id="reason_close_text" style="display: none;">We've retrieved the details below from our system. Please add any other information and your contact details and then submit your update.

                    <!-- personal details -->
                <div class="form-grid-col2">
                    <fieldset>
                        <legend>Personal Details</legend>
                        <label for="first_name">First Name<span class="wbw-required-field">*</span></label>
                        <input type="text" name="first_name" size="20" id="first_name" required>
                        <label for="last_name">Last Name<span class="wbw-required-field">*</span></label>
                        <input type="text" name="last_name" size="20" id="last_name" required>
                        <label for="email_address">Email Address<span class="wbw-required-field">*</span></label>
                        <input type="email" name="email_address" id="email_address" size="50" required>
                        <label for="add_email" class="add_email">Add this email as a contact
                            address for the group</label>
                        <div class="checkbox-group">
                            <input name="add_email" id="add_email-0" value="yes" type="checkbox">
                            <label for="add_email-0">Yes</label>
                            <label for="contact_number_confidential" class="formbuilder-number-label">Contact Number (Confidential)</label>
                            <input type="number" name="contact_number_confidential" id="contact_number_confidential">
                            <label for="group_relationship">Are you a?</label>
                            <select name="group_relationship" id="group_relationship">
                                <option value="Group Member">Group Member</option>
                                <option value="Area Trusted Servant">Area Trusted Servant</option>
                                <option value="Regional Trusted Servant">Regional Trusted Servant</option>
                                <option value="NA Member">NA Member</option>
                                <option value="Not A Member">Not A Member</option>
                            </select>
                        </div>
                    </fieldset>
                </div>
                <!-- meeting details -->
                <div class="form-grid-col1">
                    <fieldset>
                        <legend>Meeting Details</legend>

                        <div class="form-grid-col1-top">
                            <label for="meeting_name">Group Name<span class="wbw-required-field"> *</span></label>
                            <input type="text" name="meeting_name" size="50" id="meeting_name" required>
                        </div>

                        <div class="form-grid-col1-middle">
                            <div class="form-grid-col1-s1">
                                <label for="weekday_tinyintk">Meeting Day:<span class="wbw-required-field"> *</span></label>
                                <select name="weekday_tinyint" id="weekday_tinyint">
                                    <option value=1>Sunday</option>
                                    <option value=2>Monday</option>
                                    <option value=3>Tuesday</option>
                                    <option value=4>Wednesday</option>
                                    <option value=5>Thursday</option>
                                    <option value=6>Friday</option>
                                    <option value=7>Saturday</option>
                                </select>
                            </div>
                            <div class="form-grid-col1-s2">
                                <label for="start_time">Start Time<span class="wbw-required-field"> *</span></label>
                                <input type="time" name="start_time" size="10" id="start_time" required>
                            </div>
                            <div class="form-grid-col1-s3">
                                <label for="duration_hours">Duration<span class="wbw-required-field"> *</span></label>
                                <select id="duration_hours">
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
                                <select id="duration_minutes">
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
                                <input type="hidden" name="duration_time" size="10" id="duration_time" required>
                            </div>
                        </div>
                        <div class="form-grid-col1-bottom">
                            <label for="service_body_bigint">Service Committee (or Other if not known)</label>
                            <select name="service_body_bigint" id="service_body_bigint">
                            </select>
                            <label for="location_text">Location (eg: a building name)<span class="wbw-required-field"> *</span></label>
                            <input type="text" name="location_text" size="50" id="location_text" required>
                            <label for="location_street">Street Address<span class="wbw-required-field"> *</span></label>
                            <input type="text" name="location_street" size="50" id="location_street" required>
                            <label for="location_info">Extra Location Info (eg: Near the park)</label>
                            <input type="text" name="location_info" size="50" id="location_info">
                            <label for="location_municipality">City/Town/Suburb<span class="wbw-required-field"> *</span></label>
                            <input type="text" name="location_municipality" size="50" id="location_municipality" required>
                            <label for="location_province">State<span class="wbw-required-field"> *</span></label>
                            <input type="text" name="location_province" size="50" id="location_province" required>
                            <label for="location_postal_code_1">Postcode<span class="wbw-required-field"> *</span></label>
                            <input type="number" name="location_postal_code_1" size="5" max="9999" id="location_postal_code_1" required>
                            <div>
                                <label for="format-table">Meeting Format</label>
                                <table id="format-table">
                                    <tbody>
                                        <?php
                                        $bmlt_integration = new BMLTIntegration;
                                        $formatarr = $bmlt_integration->getMeetingFormats();

                                        foreach ($formatarr as $key => $value) {
                                            // error_log("key " . $key);
                                            // error_log(vdump($value));
                                            $row = '<tr>';
                                            $row .= '<td><input type="checkbox" id="format-table-' . $key . '" value="' . $key . '"></input></td>';
                                            $row .= "<td>(" . $value['key_string'] . ")</td>";
                                            $row .= "<td>" . $value['name_string'] . "</td><td>" . $value['description_string'] . "</td>";
                                            $row .= '</tr>';
                                            echo $row;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="format_shared_id_list" id="format_shared_id_list" value="">
                            </div>
                            <label for=" virtual_meeting_link">Online Meeting Link</label>
                            <input type="url" name="virtual_meeting_link" size="50" id="virtual_meeting_link">
                        </div>
                    </fieldset>
                </div>
                <!-- other details -->
                <div class="form-grid-bottom">
                    <fieldset>
                        <legend>Additional Details</legend>
                        <label for="additional_info">Additional Info</label>
                        <textarea name="additional_info" id="additional_info" rows="5" cols="50" placeholder="Provide any more detail that may help us action your meeting change request"></textarea>
                        <div id="starter_pack">
                            <label for="starter_kit_required">Starter Kit Required</label>
                            <select name="starter_kit_required" id="starter_kit_required">
                                <option value="yes" selected="true" id="starter_kit_required_yes">Yes</option>
                                <option value="no" id="starter_kit_required_no">No</option>
                            </select>
                            <label for="starter_kit_postal_address">Starter Kit Postal Address<span class="wbw-required-field"> *</span></label>
                            <textarea name="starter_kit_postal_address" id="starter_kit_postal_address" rows="5" cols="50"></textarea>
                        </div>
                    </fieldset>
                </div>
            </div>
            <br><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"></p>
        </div>
    </form>
</div>
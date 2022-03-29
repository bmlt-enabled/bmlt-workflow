<?php

if (!class_exists('BMLTIntegration')) {
    require_once(WBW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

$wbw_bmlt_test_status = get_option('wbw_bmlt_test_status', "failure");
if ($wbw_bmlt_test_status != "success") {
    wp_die("<h4>WBW Plugin Error: BMLT Server not configured and tested.</h4>");
}

wp_nonce_field('wp_rest', '_wprestnonce');

$bmlt_integration = new BMLTIntegration;
$formatarr = $bmlt_integration->getMeetingFormats();
$script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

$counties = $bmlt_integration->getMeetingCounties();
$counties = array( "Androscoggin","Aroostook","Barnstable","Belknap","Bristol","Caledonia","Carroll","Chittenden","Coos","Cumberland","Dukes","Essex","Franklin","Grafton","Hampden","Hampshire","Hancock","Hillsborough","Kent","Kennebec","Knox","Lamoille","Merrimack","Middlesex","Nantucket","Newport","Norfolk","Oxford","Penobscot","Piscataquis","Plymouth","Providence","Rockingham","Sagadahoc","Somerset","Strafford","Suffolk","Waldo","Washington","Worcester","York");

if($counties)
{
    $counties = '<select class="meeting-input" name="location_sub_province">';
    foreach ($key as $counties)
    {
        $counties .= '<option value="'.$key.'">'.$key.'</option>';
    }
    $counties .= '</select>';
}
else
{
    $counties =<<<EOD
    <label for="location_sub_province">Sub Province</label>
    <input class="meeting-input" type="text" name="location_sub_province" size="50" id="location_sub_province">
EOD;
}

$states = $bmlt_integration->getMeetingStates();
if($states)
{
    $states = '<select class="meeting-input" name="location_province">';
    foreach ($key as $states)
    {
        $states .= '<option value="'.$key.'">'.$key.'</option>';
    }
    $states .= '</select>';
}
else
{
    $states =<<<EOD
    <label for="location_province">State<span class="wbw-required-field"> *</span></label>
    <input class="meeting-input" type="text" name="location_province" size="50" id="location_province" required>
EOD;
}
?>
<div id="form_replace">
    <form action="#" method="post" id="meeting_update_form">
        <input type="hidden" name="action" value="meeting_update_form_response">
        <div class="rendered-form">
            <div>
                <label for="update_reason"">Reason For Update:</label>
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
                <label for="update_reason"">Search For Meeting:</label>
                <select class=" meeting-searcher" id="meeting-searcher">
                    <option></option>
                    </select>
                    <br><br>
                    <input type="hidden" name="meeting_id" id="meeting_id" value="">
            </div>
            <div id="meeting_content" class="form-grid">
                <div class="form-grid-top">
                    <p id="instructions"></p>
                </div>
                <!-- personal details -->
                <div id="personal_details" class="form-grid-col2">
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
                        <select name="add_email" id="add_email">
                            <option value="yes">Yes</option>
                            <option value="no" selected>No</option>
                        </select>
                        <label for="contact_number_confidential" class="formbuilder-number-label">Contact Number (Confidential)</label>
                        <input type="number" name="contact_number_confidential" id="contact_number_confidential">
                        <label for="group_relationship">Relationship to group</label>
                        <select name="group_relationship" id="group_relationship">
                            <option value="Group Member">Group Member</option>
                            <option value="Area Trusted Servant">Area Trusted Servant</option>
                            <option value="Regional Trusted Servant">Regional Trusted Servant</option>
                            <option value="NA Member">NA Member</option>
                            <option value="Not A Member">Not A Member</option>
                        </select>
                    </fieldset>
                </div>
                <!-- meeting details -->
                <div id="meeting_details" class="form-grid-col1">
                    <fieldset>
                        <legend>Meeting Details</legend>

                        <div class="form-grid-col1">
                            <label for="meeting_name">Group Name<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="meeting_name" size="50" id="meeting_name" required>
                            <label for="weekday_tinyint">Meeting Day:<span class="wbw-required-field"> *</span></label>
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
                                    <label for="start_time">Start Time<span class="wbw-required-field"> *</span></label>
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
                            <label for="service_body_bigint">Service Committee (or Other if not known)</label>
                            <select class="meeting-input" name="service_body_bigint" id="service_body_bigint">
                            </select>
                            <label for="location_text">Location (eg: a building name)<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_text" size="50" id="location_text" required>
                            <label for="location_street">Street Address<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_street" size="50" id="location_street" required>
                            <label for="location_info">Extra Location Info (eg: Near the park)</label>
                            <input class="meeting-input" type="text" name="location_info" size="50" id="location_info">
                            <label for="location_municipality">City/Town/Suburb<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_municipality" size="50" id="location_municipality" required>
                            <label for="location_sub_province">Sub Province</label>
                            <input class="meeting-input" type="text" name="location_sub_province" size="50" id="location_sub_province">
                            <label for="location_province">State<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="text" name="location_province" size="50" id="location_province" required>
                            <label for="location_postal_code_1">Postcode<span class="wbw-required-field"> *</span></label>
                            <input class="meeting-input" type="number" name="location_postal_code_1" size="5" max="99999" id="location_postal_code_1" required>
                            <label for="location_nation">Nation</label>
                            <input class="meeting-input" type="text" name="location_nation" size="50" id="location_nation">

                            <label for="display_format_shared_id_list">Meeting Formats</label>
                            <select class="meeting-input" name="display_format_shared_id_list" id="display_format_shared_id_list"></select>
                            <input type="hidden" name="format_shared_id_list" id="format_shared_id_list">

                            <label for=" virtual_meeting_link">Online Meeting Link</label>
                            <input class="meeting-input" type="url" name="virtual_meeting_link" size="50" id="virtual_meeting_link">
                        </div>
                    </fieldset>
                </div>
                <!-- other details -->
                <div class="form-grid-bottom">
                    <div id="additional_info_div">
                    <fieldset>
                        <legend>Additional Information</legend>
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
                    <br><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"></p>
                </div>
            </div>
        </div>
    </form>
</div>
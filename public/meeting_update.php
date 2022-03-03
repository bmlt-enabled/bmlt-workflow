<?php

wp_enqueue_style('select2css');
wp_enqueue_script('select2');
wp_enqueue_script('bmawjs');
wp_enqueue_script('meetingupdatejs');

$bmaw_bmlt_test_status = get_option('bmaw_bmlt_test_status', "failure");
if ($bmaw_bmlt_test_status != "success") {
    wp_die("<h4>BMAW Plugin Error: BMLT Server not configured and tested.</h4>");
}
// bmaw_service_areas_string from include
$bmaw_service_areas_string = explode(",", $bmaw_service_areas_string);
echo '<script>var bmaw_service_areas="';
$service_areas_parsed = "";
foreach ($bmaw_service_areas_string as $i) {
    $service_areas_parsed .= 'services[]=' . $i . '&';
}

echo '<input type="hidden" value="' . wp_nonce_field('wp_rest', '_wpnonce') . '">';

echo substr_replace($service_areas_parsed, "", -1) . '";';
echo 'var bmaw_bmlt_server_address = "' . get_option('bmaw_bmlt_server_address') . '"</script>';
echo '<link rel="stylesheet" href="' . esc_url(plugins_url('css/meeting-update-form.css', dirname(__FILE__))) . '">';
?>

<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js"></script>

<form action="#" method="post" id="meeting_update_form">
    <input type="hidden" name="action" value="meeting_update_form_response">
    <input type="hidden" name="meeting_update_form_nonce" value="<?php echo wp_create_nonce('meeting_update_form_nonce'); ?>" />
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
        <div id="other_reason">
            <label for="other_reason">Other Reason</label>
            <textarea name="other_reason" id="other_reason" rows="5" cols="50" required></textarea>
        </div>
        <div id="meeting_selector">
            <br>
            <select class="select2-ajax" id="meeting-searcher">
                <option></option>
            </select>
            <br><br>
            <input type="hidden" name="id_bigint" id="id_bigint" value="">
        </div>
        <div id="meeting_content">
            <p id="reason_change_text" style="display: none;">We've retrieved the details below from our system. Please make any changes and then submit your update.
            <p id="reason_other_text" style="display: none;">Please let us know the details about your meeting change.
            <p id="reason_new_text" style="display: none;">Please fill in the details of your new meeting, and whether your new meeting needs a starter kit provided, and then submit your update.
            <p id="reason_close_text" style="display: none;">We've retrieved the details below from our system. Please add any other information and your contact details and then submit your update.
            <div>
                <label for="meeting_name">Group Name<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="meeting_name" size="50" id="meeting_name" required>
            </div>
            <br>
            <div>
                <label for="day_of_the_week">Group Meets On Which Days<span class="bmaw-required-field"> *</span></label>
                <ul style="list-style-type:none;" id="day_of_the_week">
                    <li>
                        <input name="Sunday" id="weekday-0" value="Sunday" type="checkbox">
                        <label for="weekday-0">Sunday</label>
                    </li>
                    <li>
                        <input name="Monday" id="weekday-1" value="Monday" type="checkbox">
                        <label for="weekday-1">Monday</label>
                    </li>
                    <li>
                        <input name="Tuesday" id="weekday-2" value="Tuesday" type="checkbox">
                        <label for="weekday-2">Tuesday</label>
                    </li>
                    <li>
                        <input name="Wednesday" id="weekday-3" value="Wednesday" type="checkbox">
                        <label for="weekday-3">Wednesday</label>
                    </li>
                    <li>
                        <input name="Thursday" id="weekday-4" value="Thursday" type="checkbox">
                        <label for="weekday-4">Thursday</label>
                    </li>
                    <li>
                        <input name="Friday" id="weekday-5" value="Friday" type="checkbox">
                        <label for="weekday-5">Friday</label>
                    </li>
                    <li>
                        <input name="Saturday" id="weekday-6" value="Saturday" type="checkbox">
                        <label for="weekday-6">Saturday</label>
                    </li>
                </ul>
                <input type="hidden" name="weekday" id="weekday" value="">
            </div>
            <div>
                <label for="start_time">Start Time<span class="bmaw-required-field"> *</span></label>
                <input type="time" name="start_time" size="10" id="start_time" required>
            </div>
            <br>
            <div>
                <label for="duration_time">Duration<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="duration_time" size="10" id="duration_time" required>
            </div>
            <br>
            <!-- <div>
                <label for="time_zone">Time Zone</label>
                <select name="time_zone" id="time_zone">
                    <option value="Australia/Adelaide">Australian Central Time (Adelaide)</option>
                    <option value="Australia/Darwin">Australian Central Time (Darwin)</option>
                    <option value="Australia/Eucla">Australian Central Western Time (Eucla)</option>
                    <option value="Australia/Brisbane">Australian Eastern Time (Brisbane)</option>
                    <option value="Australia/Sydney">Australian Eastern Time (Sydney)</option>
                    <option value="Australia/Perth">Australian Western Time (Perth)</option>
                </select>
            </div> -->
            <br>
            <div>
                <label for="service_area">Service Committee (or Other if not known)</label>
                <select name="service_area" id="service_area">
                    <?php
                    $arr = get_option('bmaw_service_committee_option_array');
                    foreach ($arr as $key => $value) {
                        $committee = $value['name'];
                        echo '<option value="' . $committee . '">' . $committee . '</option>';
                    }
                    ?>
                </select>
            </div>
            <br>
            <div>
                <label for="location_text">Location (eg: a building name)<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="location_text" size="50" id="location_text" required>
            </div>
            <br>
            <div>
                <label for="location_street">Street Address<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="location_street" size="50" id="location_street" required>
            </div>
            <br>
            <div>
                <label for="location_info">Extra Location Info (eg: Near the park)</label>
                <input type="text" name="location_info" size="50" id="location_info">
            </div>
            <br>
            <div>
                <label for="location_municipality">City/Town/Suburb<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="location_municipality" size="50" id="location_municipality" required>
            </div>
            <br>
            <div>
                <label for="location_province">State<span class="bmaw-required-field"> *</span></label>
                <input type="text" name="location_province" size="50" id="location_province" required>
            </div>
            <br>
            <div>
                <label for="location_postal_code_1">Postcode<span class="bmaw-required-field"> *</span></label>
                <input type="number" name="location_postal_code_1" size="5" max="9999" id="location_postal_code_1" required>
            </div>
            <br>
            <div>
                <label for="format-table">Meeting Format</label>
                <table id="format-table">
                    <tbody></tbody>
                </table>
                <input type="hidden" name="formats" id="formats" value="">
            </div>
            <br>
            <div>
                <label for=" virtual_meeting_link">Online Meeting Link</label>
                <input type="url" name="virtual_meeting_link" size="50" id="virtual_meeting_link">
            </div>
            <br>
            <!-- <div>
                <label for="comments">Comments</label>
                <input type="text" name="comments" id="comments">
            </div> -->
            <br>
            <div>
                <label for="date_required">Date Change Required<span class="bmaw-required-field">*</span></label>
                <input type="date" name="date_required" size="15" id="date_required" required>
            </div>
            <br>
            <div>
                <label for="first_name">First Name<span class="bmaw-required-field">*</span></label>
                <input type="text" name="first_name" size="20" id="first_name" required>
            </div>
            <br>
            <div>
                <label for="last_name">Last Name<span class="bmaw-required-field">*</span></label>
                <input type="text" name="last_name" size="20" id="last_name" required>
            </div>
            <br>
            <div>
                <label for="email_address">Email Address<span class="bmaw-required-field">*</span></label>
                <input type="email" name="email_address" id="email_address" size="50" required>
            </div>
            <br>
            <div>
                <label for="add_email" class="add_email">Add this email as a contact
                    address for the group</label>
                <div class="checkbox-group">
                    <div>
                        <input name="add_email" id="add_email-0" value="yes" type="checkbox" checked="checked">
                        <label for="add_email-0">Yes</label>
                    </div>
                </div>
            </div>
            <br>
            <div>
                <label for="contact_number_confidential" class="formbuilder-number-label">Contact Number (Confidential)</label>
                <input type="number" name="contact_number_confidential" id="contact_number_confidential">
            </div>
            <br>
            <div>
                <label for="group_relationship">Are you a?</label>
                <select name="group_relationship" id="group_relationship">
                    <option value="Group Member">Group Member</option>
                    <option value="Area Trusted Servant">Area Trusted Servant</option>
                    <option value="Regional Trusted Servant">Regional Trusted Servant</option>
                    <option value="NA Member">NA Member</option>
                    <option value="Not A Member">Not A Member</option>
                </select>
            </div>
            <br>
            <div>
                <label for="additional_info">Additional Info</label>
                <textarea name="additional_info" id="additional_info" rows="5" cols="50"></textarea>
            </div>
            <br>
            <div id="starter_pack">
                <div>
                    <label for="starter_kit_required">Starter Kit Required</label>
                    <select name="starter_kit_required" id="starter_kit_required">
                        <option value="yes" selected="true" id="starter_kit_required_yes">Yes</option>
                        <option value="no" id="starter_kit_required_no">No</option>
                    </select>
                </div>
                <br>
                <div>
                    <label for="starter_kit_postal_address">Starter Kit Postal Address<span class="bmaw-required-field"> *</span></label>
                    <textarea name="starter_kit_postal_address" id="starter_kit_postal_address" rows="5" cols="50" required></textarea>
                </div>
            </div>
            <br><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"></p>
        </div>
    </div>
</form>
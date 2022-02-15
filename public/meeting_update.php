<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="meeting_update_form">
    <input type="hidden" name="action" value="meeting_update_form_response">
    <input type="hidden" name="meeting_update_form_nonce" value="<?php echo wp_create_nonce('meeting_update_form_nonce'); ?>" />
    <div class="rendered-form">
        <div>
            <label for="update_reason"">Reason For Update</label>
            <select name="update_reason" id="update_reason">
                <option disabled="null" selected="null">select</option>
                <option value="reason_new">New Meeting</option>
                <option value="reason_change">Change Existing Meeting</option>
                <option value="reason_close">Close Meeting</option>
                <option value="reason_other">Other</option>
                </select>
        </div>
        <div id="meeting_content">
            <div id="meeting_selector">
                <select class="select2-ajax" id="meeting-searcher">
                    <option></option>
                </select>
            </div>
            <div id="other_reason">
                <label for="other_reason">Other Reason</label>
                <input type="text" name="other_reason" id="other_reason">
            </div>
            <div>
                <label for="meeting_name">Group Name</label>
                <input type="text" name="meeting_name" id="meeting_name">
            </div>
            <div>
                <label for="day_of_the_week">Group Meets On Which Days<span class="formbuilder-required">*</span></label>
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
            </div>
            <div>
                <label for="start_time">Start Time<span class="formbuilder-required">*</span></label>
                <input type="text" name="start_time" id="start_time" required="required" aria-required="true">
            </div>
            <div>
                <label for="duration_time">Duration</label>
                <input type="text" name="duration_time" id="duration_time" required="required" aria-required="true">
            </div>
            <div>
                <label for="time_zone">Time Zone</label>
                <select name="time_zone" id="time_zone">
                    <option value="Australia/Adelaide">Australian Central Time (Adelaide)</option>
                    <option value="Australia/Darwin">Australian Central Time (Darwin)</option>
                    <option value="Australia/Eucla">Australian Central Western Time (Eucla)</option>
                    <option value="Australia/Brisbane">Australian Eastern Time (Brisbane)</option>
                    <option value="Australia/Sydney">Australian Eastern Time (Sydney)</option>
                    <option value="Australia/Perth">Australian Western Time (Perth)</option>
                </select>
            </div>
            <div>
                <label for="service_area">Committee</label>
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
            <div>
                <label for="location_text">Location (eg: a building name)</label>
                <input type="text" name="location_text" id="location_text">
            </div>
            <div>
                <label for="location_street">Street Address</label>
                <input type="text" name="location_street" id="location_street">
            </div>
            <div>
                <label for="location_info">Extra Location Info (eg: Near the park)</label>
                <input type="text" name="location_info" id="location_info">
            </div>
            <div>
                <label for="location_municipality">City/Town/Suburb</label>
                <input type="text" name="location_municipality" id="location_municipality">
            </div>
            <div>
                <label for="location_province">State</label>
                <input type="text" name="location_province" id="location_province">
            </div>
            <div>
                <label for="location_postal_code_1">Postcode</label>
                <input type="number" name="location_postal_code_1" id="location_postal_code_1">
            </div>
            <div id="formats">
                <label for="format-group">Meeting Format</label>
                <ul style="list-style-type:none;""></ul>
            </div>
            <div>
                <label for="virtual_meeting_link">Online Meeting Link</label>
                    <input type="text" name="virtual_meeting_link" id="virtual_meeting_link">
            </div>
            <div>
                <label for="comments">Comments</label>
                    <input type="text" name="comments" id="comments">
            </div>
            <div>
                <label for="date-1644381216519" class="formbuilder-date-label">Date Change Required<span class="formbuilder-required">*</span></label>
                <input type="date" name="date-1644381216519" id="date-1644381216519" required="required" aria-required="true">
            </div>
            <div>
                <label for="first_name">First Name<span class="formbuilder-required">*</span></label>
                <input type="text" name="first_name" id="first_name" required="required" aria-required="true">
            </div>
            <div>
                <label for="last_name">Last Name<span class="formbuilder-required">*</span></label>
                <input type="text" name="last_name" id="last_name" required="required" aria-required="true">
            </div>
            <div>
                <label for="email_address">Email Address<span class="formbuilder-required">*</span></label>
                <input type="text" name="email_address" id="email_address" required="required" aria-required="true">
            </div>
            <div>
                <label for="checkbox-group-1644381304426" class="formbuilder-checkbox-group-label">Add this email as contact
                    address for the group</label>
                <div class="checkbox-group">
                    <div>
                        <input name="checkbox-group-1644381304426[]" id="checkbox-group-1644381304426-0" value="yes" type="checkbox" checked="checked">
                        <label for="checkbox-group-1644381304426-0">Yes</label>
                    </div>
                </div>
            </div>
            <div>
                <label for="contact_number_confidential" class="formbuilder-number-label">Contact Number (Confidential)</label>
                <input type="number" name="contact_number_confidential" id="contact_number_confidential">
            </div>
            <div>
                <label for="radio-group-1644381392768" class="formbuilder-radio-group-label">Are you a?</label>
                <div class="radio-group">
                    <div>
                        <input name="radio-group-1644381392768" id="radio-group-1644381392768-0" value="option-1" type="radio" checked="checked">
                        <label for="radio-group-1644381392768-0">Group Member</label>
                    </div>
                    <div>
                        <input name="radio-group-1644381392768" id="radio-group-1644381392768-1" value="option-2" type="radio">
                        <label for="radio-group-1644381392768-1">Option 2</label>
                    </div>
                    <div>
                        <input name="radio-group-1644381392768" id="radio-group-1644381392768-2" value="option-3" type="radio">
                        <label for="radio-group-1644381392768-2">Option 3</label>
                    </div>
                </div>
            </div>
            <div>
                <label for="additional_info">Additional Info</label>
                <input type="text" name="additional_info" id="additional_info">
            </div>
            <div id="starter_pack">
                <div>
                    <label for="select-1644381474827" class="formbuilder-select-label">Starter Kit Required</label>
                    <select name="select-1644381474827" id="select-1644381474827">
                        <option value="yes" selected="true" id="select-1644381474827-0">Yes</option>
                        <option value="no" id="select-1644381474827-1">No</option>
                    </select>
                </div>
                <div>
                    <label for="text-1644381513895">Starter Kit Postal Address</label>
                    <input type="text" name="text-1644381513895" id="text-1644381513895">
                </div>
            </div>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"></p>
        </div>
    </div>
    <input type="hidden" name="hidden_orig_start_time" id="hidden_orig_start_time" value="">
    <input type="hidden" name="hidden_new_start_time" id="hidden_new_start_time" value="">
    <input type="hidden" name="hidden_orig_duration_time" id="hidden_orig_duration_time" value="">
    <input type="hidden" name="hidden_new_duration_time" id="hidden_new_duration_time" value="">
    <input type="hidden" name="hidden_orig_formats" id="hidden_orig_formats" value="">
    <input type="hidden" name="hidden_new_formats" id="hidden_new_formats" value="">
    <input type="hidden" name="hidden_orig_virtual_meeting_link" id="hidden_orig_virtual_meeting_link" value="">
    <input type="hidden" name="hidden_new_virtual_meeting_link" id="hidden_new_virtual_meeting_link" value="">
    <input type="hidden" name="hidden_orig_virtual_meeting_additional_info" id="hidden_orig_virtual_meeting_additional_info" value="">
    <input type="hidden" name="hidden_new_virtual_meeting_additional_info" id="hidden_new_virtual_meeting_additional_info" value="">
    <input type="hidden" name="hidden_orig_weekday" id="hidden_orig_weekday" value="">
    <input type="hidden" name="hidden_new_weekday" id="hidden_new_weekday" value="">
    <input type="hidden" name="hidden_orig_meeting_name" id="hidden_orig_meeting_name" value="">
    <input type="hidden" name="hidden_new_meeting_name" id="hidden_new_meeting_name" value="">
    <input type="hidden" name="hidden_orig_comments" id="hidden_orig_comments" value="">
    <input type="hidden" name="hidden_new_comments" id="hidden_new_comments" value="">
    <input type="hidden" name="hidden_orig_timezone" id="hidden_orig_timezone" value="">
    <input type="hidden" name="hidden_new_timezone" id="hidden_new_timezone" value="">
</form>
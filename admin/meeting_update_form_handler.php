<?php

if (!defined('ABSPATH')) exit; // die if being called directly

if (!class_exists('BMLTIntegration')) {
	require_once(WBW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

if (!(function_exists('vdump'))) {
    function vdump($object)
    {
        ob_start();
        var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}

function get_emails_by_servicebody_id($id)
{
    global $wpdb;
    global $wbw_service_bodies_access_table_name;

    $emails = array();
    $sql = $wpdb->prepare('SELECT wp_uid from ' . $wbw_service_bodies_access_table_name . ' where service_body_bigint="%d"', $id);
    $result = $wpdb->get_col($sql);
    foreach ($result as $key => $value) {
        $user = get_user_by('ID', $value);
        $emails[] = $user->user_email;
    }
    return implode(',', $emails);
}

// accepts raw string or array
function wbw_rest_success($message)
{
    if (is_array($message)) {
        $data = $message;
    } else {
        $data = array('message' => $message);
    }
    $response = new WP_REST_Response();
    $response->set_data($data);
    $response->set_status(200);
    return $response;
}

function wbw_rest_error($message, $code)
{
    return new WP_Error('wbw_error', $message, array('status' => $code));
}

function invalid_form_field($field)
{
    return wbw_rest_error('Form field "' . $field . '" is invalid.', 400);
}

function bmlt_retrieve_single_meeting($meeting_id)
{
    $wbw_bmlt_server_address = get_option('wbw_bmlt_server_address');
    $url = $wbw_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" . $meeting_id . "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $headers = array(
        "Accept: */*",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($curl);
    if (!$resp) {
        return wbw_rest_error('Server error retrieving meeting list', 500);
    }
    curl_close($curl);
    $meeting = json_decode($resp, true)[0];
    error_log(vdump($meeting));
    // how possibly can we get a meeting that is not the same as we asked for
    if ($meeting['id_bigint'] != $meeting_id) {
        return wbw_rest_error('Server error retrieving meeting list', 500);
    }
    return $meeting;
}

function meeting_update_form_handler_rest($data)
{
    error_log("in rest handler");
    error_log(vdump($data));

    $reason_new_bool = false;
    $reason_other_bool = false;
    $reason_change_bool = false;
    $reason_close_bool = false;

    // strip blanks
    foreach ($data as $key => $value) {
        if (($data[$key] === "") || ($data[$key] === NULL)) {
            unset($data[$key]);
        }
    }

    if (isset($data['update_reason'])) {
        // we use these to enforce required parameters in the next section
        $reason_new_bool = ($data['update_reason'] === 'reason_new');
        $reason_other_bool = ($data['update_reason'] === 'reason_other');
        $reason_change_bool = ($data['update_reason'] === 'reason_change');
        $reason_close_bool = ($data['update_reason'] === 'reason_close');
    }

    if (!(isset($data['update_reason']) || (!$reason_new_bool && !$reason_other_bool && !$reason_change_bool && !$reason_close_bool))) {
        return wbw_rest_error('No valid meeting update reason provided', 400);
    }

    // sanitize any input
    // array value [0] is 'input type', [1] is boolean (true if required)

    $subfields = array(
        "update_reason" => array("text", true),
        "meeting_id" => array("number", $reason_change_bool | $reason_close_bool),
        "first_name" => array("text", true),
        "last_name" => array("text", true),
        "meeting_name" => array("text", $reason_new_bool),
        "start_time" => array("text", $reason_new_bool),
        "duration_time" => array("text", $reason_new_bool),
        "location_text" => array("text", $reason_new_bool),
        "location_street" => array("text", $reason_new_bool),
        "location_info" => array("text", false),
        "location_municipality" => array("text", $reason_new_bool),
        "location_province" => array("text", $reason_new_bool),
        "location_postal_code_1" => array("number", $reason_new_bool),
        "weekday_tinyint" => array("weekday", $reason_new_bool),
        "service_body_bigint" => array("bigint", $reason_new_bool),
        "virtual_meeting_link" => array("url", false),
        "email_address" => array("email", true),
        "contact_number_confidential" => array("text", false),
        "format_shared_id_list" => array("commaseperatednumbers",  $reason_new_bool),
        "additional_info" => array("textarea", $reason_close_bool),
        "starter_kit_postal_address" => array("textarea", false),
        "starter_kit_required" => array("text", $reason_new_bool),
        "other_reason" => array("textarea", $reason_other_bool),
        "location_sub_province" => array("text", false),
        "location_nation" => array("text", false),
        "group_relationship" => array("text", true),
        "add_email" => array("yesno", true),

    );

    $sanitised_fields = array();

    // blank meeting id if not provided
    $sanitised_fields['meeting_id'] = 0;

    // sanitise all provided fields and drop all others
    foreach ($subfields as $field => $validation) {
        $field_type = $validation[0];
        $field_is_required = $validation[1];
        // if the form field is required, check if the submission is empty or non existent
        if ($field_is_required && empty($data[$field])) {
            return wbw_rest_error('Form field "' . $field . '" is required.', 400);
        }

        // sanitise only fields that have been provided
        if (isset($data[$field])) {
            switch ($field_type) {
                case ('text'):
                    $data[$field] = sanitize_text_field($data[$field]);
                    break;
                case ('yesno'):
                    if (($data[$field] !== 'yes') && ($data[$field] !== 'no')) {
                        return invalid_form_field($field);
                    }
                    break;
                case ('commaseperatednumbers'):
                    if (preg_match("/[^0-9,]/", $data[$field])) {
                        return invalid_form_field($field);
                    }
                    $data[$field] = trim($data[$field], ',');
                    break;
                case ('number'):
                case ('bigint'):
                    $data[$field] = intval($data[$field]);
                    break;
                case ('weekday'):
                    if (!(($data[$field] >= 1) && ($data[$field] <= 7))) {
                        return invalid_form_field($field);
                    }
                    break;
                case ('url'):
                    $data[$field] = esc_url_raw($data[$field], array('http', 'https'));
                    break;
                case ('email'):
                    $data[$field] = sanitize_email($data[$field]);
                    if (empty($data[$field])) {
                        return invalid_form_field($field);
                    }
                    break;
                case ('textarea'):
                    $data[$field] = sanitize_textarea_field($data[$field]);
                    break;
                    //                 case ('time'):
                    //                     if(!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9][\s]{0,1}[aApP][mM]$/', '12:34 ZM'))
                    // {
                    //     $data[$field] = "(invalid time)"
                    // }
                    //                         break;
                default:
                    wp_die("Form processing error");
                    break;
            }
            $sanitised_fields[$field] = $data[$field];
        }
    }

    // drop out everything that isnt in our approved list
    $data = array();

    // fields used throughout the rest of the form processing
    $reason = $sanitised_fields['update_reason'];
    $service_body_bigint = CONST_OTHER_SERVICE_BODY;
    if (!empty($sanitised_fields['service_body_bigint'])) {
        $service_body_bigint = $sanitised_fields['service_body_bigint'];
    }
    $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
    $submitter_email = $sanitised_fields['email_address'];
    $submission = array();


    // create our submission for the database changes_requested field
    switch ($reason) {
        case ('reason_new'):
            $subject = 'New meeting notification';

            // form fields allowed in changes_requested for this change type
            $allowed_fields = array(
                "meeting_name",
                "start_time",
                "duration_time",
                "location_text",
                "location_street",
                "location_info",
                "location_municipality",
                "location_province",
                "location_postal_code_1",
                "location_nation",
                "location_sub_province",
                "weekday_tinyint",
                "service_body_bigint",
                "virtual_meeting_link",
                "format_shared_id_list",
                "contact_number_confidential",
                "group_relationship",
                "add_email",
                "additional_info",
            );

            // new meeting - add all fields to the changes requested
            foreach ($allowed_fields as $field) {
                // make sure its not a null entry, ie not entered on the frontend form
                if (!empty($sanitised_fields[$field])) {
                    $submission[$field] = $sanitised_fields[$field];
                }
            }

            break;
        case ('reason_change'):
            // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless
            $subject = 'Change meeting notification';

            // form fields allowed in changes_requested for this change type
            $allowed_fields = array(
                "meeting_name",
                "start_time",
                "duration_time",
                "location_text",
                "location_street",
                "location_info",
                "location_municipality",
                "location_province",
                "location_postal_code_1",
                "location_nation",
                "location_sub_province",
                "weekday_tinyint",
                "service_body_bigint",
                "virtual_meeting_link",
                "format_shared_id_list",
            );

            $allowed_fields_extra = array(
                "contact_number_confidential",
                "group_relationship",
                "add_email",
                "additional_info",
            );

            $bmlt_meeting = bmlt_retrieve_single_meeting($sanitised_fields['meeting_id']);
            // error_log(vdump($meeting));

            // strip blanks from BMLT
            foreach ($bmlt_meeting as $key => $value) {
                if (($bmlt_meeting[$key] === "") || ($bmlt_meeting[$key] === NULL)) {
                    unset($bmlt_meeting[$key]);
                }
            }

            // if the user submitted something different to what is in bmlt, save it in changes
            foreach ($allowed_fields as $field) {
                // if the field is blank in bmlt, but they submitted a change, add it to the list
                if ((empty($bmlt_meeting[$field])) && (!empty($sanitised_fields[$field]))) {
                    error_log("found a blank bmlt entry " . $field);
                    $submission[$field] = $sanitised_fields[$field];
                }
                // if the field is in bmlt and its different to the submitted item, add it to the list
                else if ((!empty($bmlt_meeting[$field])) && (!empty($sanitised_fields[$field]))) {
                    if ($bmlt_meeting[$field] != $sanitised_fields[$field]) {
                        // error_log("{$field} is different");
                        // error_log("*** bmlt meeting");
                        // error_log(vdump($bmlt_meeting));
                        // error_log("*** sanitised fields");
                        // error_log(vdump($sanitised_fields));
                        // don't allow someone to modify a meeting service body
                        if ($field === 'service_body_bigint') {
                            return wbw_rest_error('Service body cannot be changed.', 400);
                        }
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }
            }

            if (!count($submission)) {
                return wbw_rest_error('Nothing was changed.', 400);
            }

            // add in extra form fields (non BMLT fields) to the submission
            foreach ($allowed_fields_extra as $field) {
                if (!empty($sanitised_fields[$field])) {
                    $submission[$field] = $sanitised_fields[$field];
                }
            }

            error_log("SUBMISSION");
            error_log(vdump($submission));
            // store away the original meeting name so we know what changed
            $submission['original_meeting_name'] = $bmlt_meeting['meeting_name'];

            break;
        case ('reason_close'):
            $subject = 'Close meeting notification';

            // form fields allowed in changes_requested for this change type
            $allowed_fields = array(
                "contact_number_confidential",
                "group_relationship",
                "add_email",
                "additional_info",
            );

            foreach ($allowed_fields as $item) {
                if (isset($sanitised_fields[$item])) {
                    $submission[$item] = $sanitised_fields[$item];
                }
            }
            // populate the meeting name so we dont need to do it again on the submission page
            $meeting = bmlt_retrieve_single_meeting($sanitised_fields['meeting_id']);
            $submission['meeting_name'] = $meeting['meeting_name'];

            break;
        case ('reason_other'):
            $subject = 'Other notification';

            // form fields allowed in changes_requested for this change type
            $allowed_fields = array(
                "contact_number_confidential",
                "group_relationship",
                "add_email",
                "other_reason",
            );

            foreach ($allowed_fields as $item) {
                if (isset($sanitised_fields[$item])) {
                    $submission[$item] = $sanitised_fields[$item];
                }
            }

            break;
        default:
            return wbw_rest_error('Invalid meeting change', 400);
    }

    error_log("SUBMISSION");
    error_log(vdump($submission));



    // id mediumint(9) NOT NULL AUTO_INCREMENT,
    // submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    // change_time datetime DEFAULT '0000-00-00 00:00:00',
    // changed_by varchar(10),
    // change_made varchar(10),
    // submitter_name tinytext NOT NULL,
    // submission_type tinytext NOT NULL,
    // submitter_email varchar(320) NOT NULL,

    // insert into submissions db
    global $wpdb;
    global $wbw_submissions_table_name;

    $wpdb->insert(
        $wbw_submissions_table_name,
        array(
            'submission_time'   => current_time('mysql', true),
            'meeting_id' => $sanitised_fields['meeting_id'],
            'submitter_name' => $submitter_name,
            'submission_type'  => $reason,
            'submitter_email' => $submitter_email,
            'changes_requested' => wp_json_encode($submission, 0, 1),
            'service_body_bigint' => $service_body_bigint
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );
    $insert_id = $wpdb->insert_id;
    // error_log("id = " . $insert_id);
    $message = array(
        "message" => 'Form submission successful, submission id ' . $insert_id,
        "form_html" => '<h3>Form submission successful, your submission id  is #' . $insert_id . '. You will also receive an email confirmation of your submission.</h3>'
    );

    // Send our emails out

    // Common email fields
    $from_address = get_option('wbw_email_from_address');


    // Send a notification to the trusted servants
    switch ($reason) {
        case "reason_new":
            $submission_type = "New Meeting";
            break;
        case "reason_close":
            $submission_type = "Close Meeting";
            break;
        case "reason_change":
            $submission_type = "Modify Meeting";
            break;
        case "reason_other":
            $submission_type = "Other Request";
            break;
    }

    $to_address = get_emails_by_servicebody_id($service_body_bigint);
    $subject = '[bmlt-workflow] ' . $submission_type . 'request received - ID ' . $insert_id;
    $body = 'Log in to <a href="' . get_site_url() . '/wp-admin/admin.php?page=wbw-submissions">WBW Submissions Page</a> to review.';
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
    wp_mail($to_address, $subject, $body, $headers);


    // Send email to the submitter
    $to_address = $submitter_email;
    $subject = "NA Meeting Change Request Acknowledgement - Submission ID " . $insert_id;

    $template = get_option('wbw_submitter_email_template');

    $subfield = '{field:submission}';
    $subwith = submission_format($submission);
    $template = str_replace($subfield, $subwith, $template);

    $body = $template;

    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
    error_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . vdump($headers));
    wp_mail($to_address, $subject, $body, $headers);

    // return wbw_rest_success($message);
    return;
}

function submission_format($submission)
{

    $bmlt = new BMLTIntegration;
    $formats = $bmlt->getMeetingFormats();

    $table = '';

    foreach ($submission as $key => $value) {
        switch ($key) {
            case "start_time":
                $table .= '<tr><td>Start Time:</td><td>' . $value . '</td></tr>';
                break;
            case "duration":
                $table .= '<tr><td>Duration:</td><td>' . $value . '</td></tr>';
                break;
            case "location_text":
                $table .= '<tr><td>Location:</td><td>' . $value . '</td></tr>';
                break;
            case "location_street":
                $table .= '<tr><td>Street:</td><td>' . $value . '</td></tr>';
                break;
            case "location_info":
                $table .= '<tr><td>Location Info:</td><td>' . $value . '</td></tr>';
                break;
            case "location_municipality":
                $table .= '<tr><td>Municipality:</td><td>' . $value . '</td></tr>';
                break;
            case "location_province":
                $table .= '<tr><td>Province/State:</td><td>' . $value . '</td></tr>';
                break;
            case "location_sub_province":
                $table .= '<tr><td>SubProvince:</td><td>' . $value . '</td></tr>';
                break;
            case "location_nation":
                $table .= '<tr><td>Nation:</td><td>' . $value . '</td></tr>';
                break;
            case "location_postal_code_1":
                $table .= '<tr><td>PostCode:</td><td>' . $value . '</td></tr>';
                break;
            case "group_relationship":
                $table .= '<tr><td>Relationship to Group:</td><td>' . $value . '</td></tr>';
                break;
            case "weekday_tinyint":
                $weekdays = ["Error", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                $table .= "<tr><td>Meeting Day:</td><td>" . $weekdays[$value] . '</td></tr>';
                break;
            case "additional_info":
                $table .= '<tr><td>Additional Info:</td><td>' . $value . '</td></tr>';
                break;
            case "other_reason":
                $table .= '<tr><td>Other Reason:</td><td>' . $value . '</td></tr>';
                break;
            case "contact_number_confidential":
                $table .= "<tr><td>Contact number (confidential):</td><td>" . $value . '</td></tr>';
                break;
            case "add_email":
                $result = ($value === 'yes' ? 'Yes' : 'No');
                $table .= '<tr><td>Add email to meeting:</td><td>' . $result . '</td></tr>';
                break;

            case "format_shared_id_list":
                $friendlyname = "Meeting Formats";
                // convert the meeting formats to human readable
                $friendlydata = "";
                $strarr = explode(',', $value);
                foreach ($strarr as $key) {
                    $friendlydata .= "(" . $formats[$key]["key_string"] . ")-" . $formats[$key]["name_string"] . " ";
                }
                $table .= "<tr><td>Meeting Formats:</td><td>" . $friendlydata . '</td></tr>';
                break;
        }
    }

    return $table;
}

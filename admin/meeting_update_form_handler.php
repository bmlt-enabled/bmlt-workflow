<?php

function vdump($object)
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
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

    // how possibly can we get a meeting that is not the same as we asked for
    if($meeting['meeting_id_bigint']!=$meeting_id)
    {
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

    // sanitize the input
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
    );

    $sanitised_fields = array();

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

    $reason = $sanitised_fields['update_reason'];

    $service_body_bigint = CONST_OTHER_SERVICE_BODY;
    if (!empty($sanitised_fields['service_body_bigint'])) {
        $service_body_bigint = $sanitised_fields['service_body_bigint'];
    }

    switch ($reason) {
        case ('reason_new'):
            $subject = 'New meeting notification';

            // these are the form fields we'll accept
            $submission = array();

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
                "weekday_tinyint",
                "service_body_bigint",
                "virtual_meeting_link",
                "format_shared_id_list"
            );

            // new meeting - add all fields to the changes requested
            foreach ($allowed_fields as $field) {
                // make sure its not a null entry, ie not entered on the frontend form
                if (array_key_exists($field, $sanitised_fields)) {
                    $submission[$field] = $sanitised_fields[$field];
                }
            }

            $submission['meeting_id'] = 0;

            break;
        case ('reason_change'):
            $subject = 'Change meeting notification';
            // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless

            // these are the form fields we'll accept
            $submission = array();

            $change_subfields = array(
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
                "additional_info",
                "weekday_tinyint",
                "service_body_bigint",
                "virtual_meeting_link",
                "format_shared_id_list"
            );

            // add in the meeting id
            $meeting_id = $sanitised_fields['meeting_id'];

            $bmlt_meeting = bmlt_retrieve_single_meeting($meeting_id);
            // error_log(vdump($meeting));

            // strip blanks from BMLT
            foreach ($bmlt_meeting as $key => $value) {
                if (($bmlt_meeting[$key] === "") || ($bmlt_meeting[$key] === NULL)) {
                    unset($bmlt_meeting[$key]);
                }
            }

            // if the user submitted something different to what is in bmlt, save it in changes
            foreach ($change_subfields as $field) {
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

            // store away the original meeting name so we know what changed
            $submission['original_meeting_name'] = $bmlt_meeting['meeting_name'];
            // store away the meeting id
            $submission['meeting_id'] = $meeting_id;

            break;
        case ('reason_close'):
            $subject = 'Close meeting notification';

            $submission = array();

            $allowed_fields = array(
                "meeting_id",
                "update_reason",
                "first_name",
                "last_name",
                "email_address",
                "contact_number_confidential",
                "additional_info"
            );

            foreach ($allowed_fields as $item) {
                if (isset($sanitised_fields[$item])) {
                    $submission[$item] = $sanitised_fields[$item];
                }
            }
            // populate the meeting name so we dont need to do it again on the submission page
            $meeting = bmlt_retrieve_single_meeting($submission['meeting_id']);
            $submission['meeting_name'] = $meeting['meeting_name'];

            break;
        case ('reason_other'):
            $submission = array();

            $subject = 'Other notification';
            $allowed_fields = array(
                "update_reason",
                "first_name",
                "last_name",
                "email_address",
                "contact_number_confidential",
                "other_reason"
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

    $cc_address = "";
    $to_address = "";

    $from_address = get_option('wbw_email_from_address');

    // Do field replacement in to: and cc: address
    $subfield = '{field:email_address}';
    $subwith = $sanitised_fields['email_address'];
    $to_address = str_replace($subfield, $subwith, $to_address);
    if (!empty($cc_address)) {
        $cc_address = str_replace($subfield, $subwith, $cc_address);
    }

    $body = "mesage";
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address, 'Cc: ' . $cc_address);
    // Send the email
    // wp_mail($to_address, $subject, $body, $headers);

    // Handle the FSO emails
    if ($reason == "reason_new") {
        if ((!empty($sanitised_fields['starter_kit_required'])) && ($sanitised_fields['starter_kit_required'] === 'yes') && (!empty($sanitised_fields['starter_kit_postal_address']))) {
            $template = get_option('wbw_fso_email_template');
            $subject = 'Starter Kit Request';
            $to_address = get_option('wbw_fso_email_address');
            foreach ($subfields as $field => $formattype) {
                $subfield = '{field:' . $field . '}';
                if ((isset($sanitised_fields[$field])) && (!empty($sanitised_fields[$field]))) {
                    $subwith = $sanitised_fields[$field];
                } else {
                    $subwith = '(blank)';
                }
                $template = str_replace($subfield, $subwith, $template);
            }
            $body = $template;
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
            wp_mail($to_address, $subject, $body, $headers);
        }
    }

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

    $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
    $db_reason = '';
    switch ($reason) {
        case 'reason_new':
        case 'reason_change':
        case 'reason_close':
        case 'reason_other':
            $db_reason = $reason;
            break;
        default:
            return '{"response":"invalid change type"}';
    }

    $submitter_email = $sanitised_fields['email_address'];

    $wpdb->insert(
        $wbw_submissions_table_name,
        array(
            'submission_time'   => current_time('mysql', true),
            'submitter_name' => $submitter_name,
            'submission_type'  => $db_reason,
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

    return wbw_rest_success($message);
}

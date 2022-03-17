<?php

function vdump($object)
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}

function meeting_update_form_handler_rest($data)
{
    error_log("in rest handler");
    error_log(vdump($data));

    $reason_new_bool = false;
    $reason_other_bool = false;
    $reason_change_bool = false;
    $reason_close_bool = false;

    if (isset($data['update_reason'])) {
        $reason_new_bool = ($data['update_reason'] === 'reason_new');
        $reason_other_bool = ($data['update_reason'] === 'reason_other');
        $reason_change_bool = ($data['update_reason'] === 'reason_change');
        $reason_close_bool = ($data['update_reason'] === 'reason_close');
    }

    // error_log("reason_new_bool " . vdump($reason_new_bool));
    // error_log("reason_other_bool " . vdump($reason_other_bool));
    // error_log("reason_change_bool " . vdump($reason_change_bool));
    // error_log("reason_close_bool " . vdump($reason_close_bool));
    // error_log("new|change|close " . vdump($reason_new_bool | $reason_change_bool | $reason_close_bool));

    if (!(isset($data['update_reason']) || (!$reason_new_bool && !$reason_other_bool && !$reason_change_bool && !$reason_close_bool))) {
        wp_die("No valid meeting update reason provided");
    }

    // sanitize the input
    // subfields value is 'input type', boolean (true if required)

    $subfields = array(
        "update_reason" => array("text", true),
        "first_name" => array("text", true),
        "last_name" => array("text", true),
        "meeting_name" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "start_time" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "duration_time" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_text" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_street" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_info" => array("text", false),
        "location_municipality" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_province" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "location_postal_code_1" => array("number", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "weekday_tinyint" => array("weekday", $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "virtual_meeting_link" => array("url", false),
        "email_address" => array("email", true),
        "contact_number_confidential" => array("text", false),
        // "time_zone",
        "format_shared_id_list" => array("text",  $reason_new_bool | $reason_change_bool | $reason_close_bool),
        "additional_info" => array("textarea", false),
        "starter_kit_postal_address" => array("textarea", false),
        "starter_kit_required" => array("text", false),
        "other_reason" => array("textarea", $reason_other_bool)
        // "comments"
    );

    foreach ($subfields as $field => $validation) {
        $field_type = $validation[0];

        // if the form field is required, check if the submission is empty or non existent
        if ($validation[1] && (!isset($data[$field]) || (empty($data[$field])))) {
            wp_die("Missing required form field " . $field);
        }
        switch ($field_type) {
            case ('text'):
                $data[$field] = sanitize_text_field($data[$field]);
                break;
            case ('number'):
                $data[$field] = intval($data[$field]);
                break;
            case ('weekday'):
                if (!(($data[$field] >= 1) && ($data[$field] <= 7))) {
                    wp_die("Invalid form field input");
                }
                break;
            case ('url'):
                $data[$field] = esc_url_raw($data[$field], array('http', 'https'));
                break;
            case ('email'):
                $data[$field] = sanitize_email($data[$field]);
                if (empty($data[$field])) {
                    wp_die("Invalid form field input");
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
                $data[$field] = "UNSANITISED";
        }
    }

    $reason = $data['update_reason'];

    // these are the form fields we'll accept
    $changes = array();

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
        "weekday_tinyint",
        "virtual_meeting_link",
        "format_shared_id_list"
    );

    switch ($reason) {
        case ('reason_new'):
            $subject = 'New meeting notification';

            // new meeting - add all fields to the changes requested
            foreach ($change_subfields as $field) {
                $changes[$field] = $data[$field];
            }

            $changes['meeting_id'] = 0;

            break;
        case ('reason_change'):
            $subject = 'Change meeting notification';
            // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless

            if (isset($data['meeting_id'])) {
                if (!is_numeric($data['meeting_id'])) {
                    wp_die("Invalid meeting id");
                }
                $meeting_id = $data['meeting_id'];
            }

            // add in the meeting id
            $changes['meeting_id'] = $meeting_id;

            // get the meeting details from BMLT so we can compare them
            $bmaw_bmlt_server_address = get_option('bmaw_bmlt_server_address');
            $url = $bmaw_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" . $meeting_id . "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $headers = array(
                "Accept: */*",
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $resp = curl_exec($curl);
            if (!$resp) {
                wp_die("curl failed");
            }
            curl_close($curl);
            $meeting = json_decode($resp, true)[0];

            foreach ($change_subfields as $field) {
                if (array_key_exists($field, $meeting)) {
                    if ($meeting[$field] != $data[$field]) {
                        $changes[$field] = $data[$field];
                    }
                }
            }



            break;
        case ('reason_close'):
            $subject = 'Close meeting notification';
            wp_die('Not implemented');
            break;
        case ('reason_other'):
            $subject = 'Meeting notification - Other';
            wp_die('Not implemented');
            break;
        default:
            wp_die('Invalid meeting change');
    }

    $cc_address = "";
    $to_address = "";
    // $service_committees = get_option('bmaw_service_committee_option_array');
    // foreach ($service_committees as $key => $value) {
    //     if ($value['name'] == $data['service_area']) {
    //         $cc_address = $value['e2'];
    //         $to_address = $value['e1'];
    //         break;
    //     }
    // }

    // if (empty($to_address)) {
    //     wp_die(("No valid service committee found."));
    // }

    $from_address = get_option('bmaw_email_from_address');

    // Do field replacement in to: and cc: address
    $subfield = '{field:email_address}';
    $subwith = $data['email_address'];
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
        if (($data['starter_kit_required'] === 'yes') && (!empty($data['starter_kit_postal_address']))) {
            $template = get_option('bmaw_fso_email_template');
            $subject = 'Starter Kit Request';
            $to_address = get_option('bmaw_fso_email_address');
            foreach ($subfields as $field => $formattype) {
                $subfield = '{field:' . $field . '}';
                if ((isset($data[$field])) && (!empty($data[$field]))) {
                    $subwith = $data[$field];
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
    global $bmaw_submissions_table_name;

    $submitter_name = $data['first_name'] . " " . $data['last_name'];
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

    $submitter_email = $data['email_address'];

    $wpdb->insert(
        $bmaw_submissions_table_name,
        array(
            'submission_time'   => current_time('mysql', true),
            'submitter_name' => $submitter_name,
            'submission_type'  => $db_reason,
            'submitter_email' => $submitter_email,
            'changes_requested' => wp_json_encode($changes, 0, 1)
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
    error_log("id = " . $insert_id);
    exit("<h3>Form submission successful</h3>");
    // wp_redirect( 'https://www.google.com' );
}

<?php

function meeting_update_form_handler_rest($data)
{
    error_log("in rest handler");
    error_log($data);

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
        "virtual_meeting_link" => array("url", false),
        "email_address" => array("email", true),
        "contact_number_confidential" => array("text", false),
        // "time_zone",
        "formats" => array("text", false),
        "weekday" => array("text", $reason_new_bool | $reason_change_bool | $reason_close_bool),
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
    switch ($reason) {
        case ('reason_new'):
            $subject = 'New meeting notification';
            break;
        case ('reason_change'):
            $subject = 'Change meeting notification';
            break;
        case ('reason_close'):
            $subject = 'Close meeting notification';
            break;
        case ('reason_other'):
            $subject = 'Meeting notification - Other';
            break;
        default:
            wp_die('Invalid meeting change');
    }

    if (($reason == "reason_change") || ($reason == 'reason_close')) {
        if (isset($data['id_bigint'])) {
            $meeting_id = $data['id_bigint'];
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
            $meeting = json_decode($resp, true);

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
                "virtual_meeting_link",
                "formats"
            );

            switch ($reason) {
                case 'reason_change':
                    foreach ($change_subfields as $field) {
                        if (array_key_exists($field, $meeting)) {
                            if ($meeting[$field] != $data[$field]) {
                                $changes[$field] = $data[$field];
                            }
                        }
                    }

                    break;

                case 'reason_new':
                    foreach ($change_subfields as $field) {
                        $changes[$field] = $data[$field];
                    }

                    break;

                default:
                    break;
            }
        } else {
            wp_die("meeting id not set");
        }
    }

    $cc_address = "";
    $to_address = "";
    $service_committees = get_option('bmaw_service_committee_option_array');
    foreach ($service_committees as $key => $value) {
        if ($value['name'] == $data['service_area']) {
            $cc_address = $value['e2'];
            $to_address = $value['e1'];
            break;
        }
    }

    if (empty($to_address)) {
        wp_die(("No valid service committee found."));
    }

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
    wp_mail($to_address, $subject, $body, $headers);

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
        case ('reason_new'):
            $db_reason = 'New Meeting';
            break;
        case ('reason_change'):
            $db_reason = 'Change Meeting';
            break;
        case ('reason_close'):
            $db_reason = 'Close Meeting';
            break;
        case ('reason_other'):
            $db_reason = 'Other Meeting';
            break;
    }
    $submitter_email = $data['email_address'];

    $wpdb->insert(
        $bmaw_submissions_table_name,
        array(
            'submission_time'   => current_time('mysql', true),
            'submitter_name' => $submitter_name,
            'submission_type'  => $db_reason,
            'submitter_email' => $submitter_email,
            'changes_requested' => serialize($changes)
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );

    exit("<h3>Form submission successful</h3>");
    // wp_redirect( 'https://www.google.com' );
}

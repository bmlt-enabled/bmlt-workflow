<?php
function vdump($object)
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}

function meeting_update_form_handler()
{

    if (isset($_POST['meeting_update_form_nonce']) && wp_verify_nonce($_POST['meeting_update_form_nonce'], 'meeting_update_form_nonce')) {

        $reason_new_bool = false;
        $reason_other_bool = false;
        $reason_change_bool = false;
        $reason_close_bool = false;

        if (isset($_POST['update_reason'])) {
            $reason_new_bool = ($_POST['update_reason'] === 'reason_new');
            $reason_other_bool = ($_POST['update_reason'] === 'reason_other');
            $reason_change_bool = ($_POST['update_reason'] === 'reason_change');
            $reason_close_bool = ($_POST['update_reason'] === 'reason_close');
        }

        error_log("reason_new_bool " . vdump($reason_new_bool));
        error_log("reason_other_bool " . vdump($reason_other_bool));
        error_log("reason_change_bool " . vdump($reason_change_bool));
        error_log("reason_close_bool " . vdump($reason_close_bool));
        error_log("new|change|close " . vdump($reason_new_bool | $reason_change_bool | $reason_close_bool));

        if (!(isset($_POST['update_reason']) || (!$reason_new_bool && !$reason_other_bool && !$reason_change_bool && !$reason_close_bool))) {
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
            if ($validation[1] && (!isset($_POST[$field])||(empty($_POST[$field])))) {
                wp_die("Missing required form field " . $field);
            }
            switch ($field_type) {
                case ('text'):
                    $_POST[$field] = sanitize_text_field($_POST[$field]);
                    break;
                case ('number'):
                    $_POST[$field] = intval($_POST[$field]);
                    break;
                case ('url'):
                    $_POST[$field] = esc_url_raw($_POST[$field], array('http', 'https'));
                    break;
                case ('email'):
                    $_POST[$field] = sanitize_email($_POST[$field]);
                    if (empty($_POST[$field])) {
                        wp_die("Invalid form field input");
                    }
                    break;
                case ('textarea'):
                    $_POST[$field] = sanitize_textarea_field($_POST[$field]);
                    break;
                    //                 case ('time'):
                    //                     if(!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9][\s]{0,1}[aApP][mM]$/', '12:34 ZM'))
                    // {
                    //     $_POST[$field] = "(invalid time)"
                    // }
                    //                         break;
                default:
                    $_POST[$field] = "UNSANITISED";
            }
        }


        if (isset($_POST['update_reason'])) {
            $reason = $_POST['update_reason'];
            switch ($reason) {
                case ('reason_new'):
                    $template = get_option('bmaw_new_meeting_template');
                    $subject = 'New meeting notification';
                    break;
                case ('reason_change'):
                    $template = get_option('bmaw_existing_meeting_template');
                    $subject = 'Change meeting notification';
                    break;
                case ('reason_close'):
                    $template = get_option('bmaw_close_meeting_template');
                    $subject = 'Close meeting notification';
                    break;
                case ('reason_other'):
                    $template = get_option('bmaw__meeting_template');
                    $subject = 'Meeting notification - Other';
                    break;
                default:
                    wp_die('invalid meeting reason');
            }

            if (($reason == "reason_change") || ($reason == 'reason_close')) {
                if (isset($_POST['id_bigint'])) {
                    $meeting_id = $_POST['id_bigint'];
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
                } else {
                    wp_die("meeting id not set");
                }
            }
            $orig_values = array();

            if ($reason == "reason_change") {
                // field substitution from BMLT
                $orig_subfields = array(
                    "meeting_name" => "orig_meeting_name",
                    "start_time" => "orig_start_time",
                    "duration_time" => "orig_duration_time",
                    // "time_zone" => "orig_time_zone",
                    "location_text" => "orig_location_text",
                    "location_street" => "orig_location_street",
                    "location_info" => "orig_location_info",
                    "location_municipality" => "orig_location_municipality",
                    "location_province" => "orig_location_province",
                    "location_postal_code_1" => "orig_location_postal_code_1",
                    "virtual_meeting_link" => "orig_virtual_meeting_link",
                    // "comments" => "orig_comments",
                    // "email_address" => "orig_email_address",
                    // "contact_number_confidential" => "contact_number_confidential",
                    "formats" => "orig_formats"
                    // "orig_weekday",
                );

                // Do field replacements in template
                foreach ($orig_subfields as $bmlt_field => $sub_value) {
                    $subfield = '{field:' . $sub_value . '}';
                    if (!empty($meeting[0][$bmlt_field])) {
                        $subwith = $meeting[0][$bmlt_field];
                    } else {
                        $subwith = '(blank)';
                    }
                    $template = str_replace($subfield, $subwith, $template);
                    $orig_values[$subfield] = $subwith;
                }
                // special case for weekday
                $weekdays = array(0 => "Sunday", 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday");
                $idx = $meeting[0]['weekday_tinyint'] - 1;
                $orig_values['{field:orig_weekday}'] = $weekdays[$idx];
                //                $template = str_replace('{field:orig_weekday}', $weekdays[$idx], $template);
            }
        }

        // Do field replacements in template
        foreach ($subfields as $field => $formattype) {
            $subfield = '{field:' . $field . '}';
            // $subfield = 'style';
            if ((isset($_POST[$field])) && (!empty($_POST[$field]))) {
                $subwith = $_POST[$field];
            } else {
                $subwith = '(blank)';
            }
            // special case for meeting change to handle delta
            if ($reason == 'reason_change') {
                if (array_key_exists('{field:orig_' . $field . '}', $orig_values)) {
                    if ($subwith == $orig_values['{field:orig_' . $field . '}']) {
                        $subwith = '(no change)';
                    } else {
                        $subwith = '<b>' . $subwith . '</b>';
                    }
                }
            }
            $template = str_replace($subfield, $subwith, $template);
        }

        $cc_address = "";
        $to_address = "";
        $service_committees = get_option('bmaw_service_committee_option_array');
        foreach ($service_committees as $key => $value) {
            if ($value['name'] == $_POST['service_area']) {
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
        $subwith = $_POST['email_address'];
        $to_address = str_replace($subfield, $subwith, $to_address);
        if (!empty($cc_address)) {
            $cc_address = str_replace($subfield, $subwith, $cc_address);
        }

        $body = $template;
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address, 'Cc: ' . $cc_address);
        // Send the email
        wp_mail($to_address, $subject, $body, $headers);

        // Handle the FSO emails
        if ($reason == "reason_new") {
            if (($_POST['starter_kit_required'] === 'yes') && (!empty($_POST['starter_kit_postal_address']))) {
                $template = get_option('bmaw_fso_email_template');
                $subject = 'Starter Kit Request';
                $to_address = get_option('bmaw_fso_email_address');
                foreach ($subfields as $field => $formattype) {
                    $subfield = '{field:' . $field . '}';
                    if ((isset($_POST[$field])) && (!empty($_POST[$field]))) {
                        $subwith = $_POST[$field];
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

        exit("<h3>Form submission successful</h3>");
        // wp_redirect( 'https://www.google.com' );
    } else {
        wp_die('invalid nonce');
    }
}

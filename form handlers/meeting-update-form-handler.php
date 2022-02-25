<?php
function meeting_update_form_handler()
{

    if (isset($_POST['meeting_update_form_nonce']) && wp_verify_nonce($_POST['meeting_update_form_nonce'], 'meeting_update_form_nonce')) {

        // sanitize the input
        $subfields = array(
            "first_name" => "text",
            "last_name" => "text",
            "meeting_name" => "text",
            "start_time" => "text",
            "duration_time" => "text",
            "location_text" => "text",
            "location_street" => "text",
            "location_info" => "text",
            "location_municipality" => "text",
            "location_province" => "text",
            "location_postal_code_1" => "number",
            "virtual_meeting_link" => "url",
            "email_address" => "email",
            "contact_number_confidential" => "text",
            // "time_zone",
            "formats" => "text",
            "weekday" => "text",
            "additional_info" => "textarea",
            "starter_kit_postal_address" => "textarea",
            "starter_kit_required" => "text"
            // "comments"
        );

        foreach ($subfields as $field => $field_type) {
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
            dbg("update reason = " . $_POST['update_reason']);
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
                    dbg("getting http from server");
                    $meeting_id = $_POST['id_bigint'];
                    $bmaw_bmlt_server_address = get_option('bmaw_bmlt_server_address');
                    $url = $bmaw_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" . $meeting_id . "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

                    dbg("url = " . $url);
                    $curl = curl_init($url);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                    $headers = array(
                        "Accept: */*",
                    );
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

                    $resp = curl_exec($curl);
                    dbg("curl returned " . gettype($resp));
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
                dbg("** change template before");
                dbg($template);
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
                $template = str_replace('{field:orig_weekday}', $weekdays[$idx], $template);
                dbg("weekday lookup = " . $weekdays[$idx]);
                dbg("** change template after");
                dbg($template);
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
                dbg("* Found our service area! To = " . $value['e1'] . " CC: " . $value['e2']);
                $cc_address = $value['e2'];
                $to_address = $value['e1'];
                break;
            }
        }

        if (empty($to_address) || empty($cc_address)) {
            wp_die(("No valid service committee found."));
        }

        $from_address = get_option('bmaw_email_from_address');

        // Do field replacement in to: and cc: address
        $subfield = '{field:email_address}';
        $subwith = $_POST['email_address'];
        $to_address = str_replace($subfield, $subwith, $to_address);
        $cc_address = str_replace($subfield, $subwith, $cc_address);

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
dbg("sending fso email ".$to_address.",".$subject.",".$body.",".$headers);
                wp_mail($to_address, $subject, $body, $headers);
            }
        }

        exit("<h3>Form submission successful</h3>");
        // wp_redirect( 'https://www.google.com' );
    } else {
        wp_die('invalid nonce');
    }
}

<?php 
function meeting_update_form_handler()
{

    if (isset($_POST['meeting_update_form_nonce']) && wp_verify_nonce($_POST['meeting_update_form_nonce'], 'meeting_update_form_nonce')) {

        // sanitize the input
        // $nds_user_meta_key = sanitize_key( $_POST['nds']['user_meta_key'] );
        // $nds_user_meta_value = sanitize_text_field( $_POST['nds']['user_meta_value'] );
        // $nds_user =  get_user_by( 'login',  $_POST['nds']['user_select'] );
        // $nds_user_id = absint( $nds_user->ID ) ;

        if (isset($_POST['update_reason'])) {
            $reason = $_POST['update_reason'];
            dbg("update reason = " . $_POST['update_reason']);
            switch ($reason) {
                case ('reason_new'):
                    $template = file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_new_meeting_email_template.html');
                    $subject = 'New meeting notification';
                    break;
                case ('reason_change'):
                    $template = file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_existing_meeting_email_template.html');
                    $subject = 'Change meeting notification';
                    break;
                case ('reason_close'):
                    $template = file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_close_meeting_email_template.html');
                    $subject = 'Close meeting notification';
                    break;
                case ('reason_other'):
                    $template = file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_other_meeting_email_template.html');
                    $subject = 'Meeting notification - Other';
                    break;
                default:
                    wp_die('invalid meeting reason');
            }

            if (isset($_POST['id_bigint'])) {
                if (($reason == "reason_change") || ($reason == 'reason_close')) {
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

                // <input type="hidden" name="id_bigint" id="id_bigint" value="">
                // <input type="text" name="other_reason" id="other_reason">
                // <input type="text" name="meeting_name" size="50" id="meeting_name">
                // <input type="text" name="start_time" id="start_time" required>
                // <input type="text" name="duration_time" id="duration_time" required>
                // <select name="time_zone" id="time_zone">
                // <select name="service_area" id="service_area">
                // <input type="text" name="location_text" id="location_text">
                // <input type="text" name="location_street" size="50" id="location_street">
                // <input type="text" name="location_info" id="location_info">
                // <input type="text" name="location_municipality" id="location_municipality">
                // <input type="text" name="location_province" id="location_province">
                // <input type="number" name="location_postal_code_1" max="9999" id="location_postal_code_1">
                //     <input type="url" name="virtual_meeting_link" size="50" id="virtual_meeting_link">
                // <input type="text" name="comments" id="comments">
                // <input type="date" name="date_required" id="date_required" required>
                // <input type="text" name="first_name" id="first_name" required>
                // <input type="text" name="last_name" id="last_name" required>
                // <input type="email" name="email_address" id="email_address" size="50" required>
                //         <input name="checkbox-group-1644381304426[]" id="checkbox-group-1644381304426-0" value="yes" type="checkbox" checked="checked">
                // <input type="number" name="contact_number_confidential" id="contact_number_confidential">
                // <select name="group_relationship" id="group_relationship">
                // <textarea name="additional_info" id="additional_info" rows="5" cols="50"></textarea>

                if ($reason == "reason_change") {
                    dbg("** change template before");
                    dbg($template);
                    // field substitution from BMLT
                    $subfields = array(
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
                        "comments" => "orig_comments",
                        "email_address" => "orig_email_address",
                        "contact_number_confidential" => "contact_number_confidential",
                        "formats" => "orig_formats"
                        // "orig_weekday",
                    );

                    // Do field replacements in template
                    foreach ($subfields as $bmlt_field => $sub_value) {
                        $subfield = '{field:' . $sub_value . '}';
                        if (!empty($meeting[0][$bmlt_field])) {
                            $subwith = $meeting[0][$bmlt_field];
                            $template = str_replace($subfield, $subwith, $template);
                        }
                    }
                    dbg("** change template after");
                    dbg($template);
                }
            }

            dbg("** template before");
            dbg($template);
            // field substitution from form
            $subfields = array(
                "first_name",
                "last_name",
                "meeting_name",
                "duration_time",
                // "time_zone",
                "formats",
                "weekday",
                "comments"
            );

            // Do field replacements in template
            foreach ($subfields as $field) {
                $subfield = '{field:' . $field . '}';
                // $subfield = 'style';
                $subwith = $_POST[$field];
                if (isset($_POST[$field])) {
                    $template = str_replace($subfield, $subwith, $template);
                }
            }
            dbg("** template after");
            dbg($template);

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
            dbg('sending mail');
            wp_mail($to_address, $subject, $body, $headers);
            dbg('mail sent');
            // redirect the user to the appropriate page
            // wp_redirect( 'https://www.google.com' );
            exit;
        } else {
            wp_die('invalid nonce');
        }
    }
}
?>

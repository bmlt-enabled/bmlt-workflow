<?php

/**
 * Plugin Name: BMLT Meeting Admin Workflow
 * Plugin URI: 
 * Description: BMLT Meeting Admin Workflow
 * Version: 1.0
 * Author: @nb-bmlt
 * Author URI: 
 **/

define('THIS_PLUGIN_URL', plugin_dir_path(__FILE__));

function dbg($logmsg)
{
    $log = plugin_dir_path(__FILE__) . 'debug.log';
    error_log($logmsg . PHP_EOL, 3, $log);
}

function meeting_update_form($atts = [], $content = null, $tag = '')
{
    // dbg("atts = ".$atts);
    $parsed_atts = shortcode_atts(
        array(
            'service-areas' => '1',
        ),
        $atts,
        $tag
    );
    $bmaw_service_areas_string = preg_replace("/[^0-9,]/", "", $parsed_atts['service-areas']);

    ob_start();
    include('public/meeting_update.php');
    $content .= ob_get_clean();
    return $content;
}

function enqueue_form_deps()
{
    wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
    wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
    wp_enqueue_script('bmawjs', plugin_dir_url(__FILE__) . 'js/script_includes.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/script_includes.js'), true);
    wp_enqueue_script('bmaw-meetingupdatejs', plugin_dir_url(__FILE__) . 'js/meeting_update.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/meeting_update.js'), true);
}

function bmaw_admin_scripts($hook)
{
    if ($hook != 'settings_page_bmaw-settings') {
        return;
    }
    wp_enqueue_style('bmaw-admin-css', plugin_dir_url(__FILE__) . 'css/admin_page.css', false, filemtime(plugin_dir_path(__FILE__) . 'css/admin_page.css'), 'all');
    wp_enqueue_script('bmawjs', plugin_dir_url(__FILE__) . 'js/script_includes.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/script_includes.js'), true);
}

function bmaw_options_page()
{

    add_options_page(
        'BMAW Settings', // page <title>Title</title>
        'BMAW Settings', // menu link text
        'manage_options', // capability to access the page
        'bmaw-settings', // page URL slug
        'display_admin_options_page', // callback function with content
        2 // priority
    );
}

function add_plugin_link($plugin_actions, $plugin_file)
{

    $new_actions = array();
    if (basename(plugin_dir_path(__FILE__)) . '/meeting-admin-workflow.php' === $plugin_file) {
        $new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'comment-limiter'), esc_url(admin_url('options-general.php?page=bmaw-settings')));
    }

    return array_merge($new_actions, $plugin_actions);
}

// actions, shortcodes and filters
add_action('admin_post_nopriv_meeting_update_form_response', 'meeting_update_form_response');
add_action('admin_post_meeting_update_form_response', 'meeting_update_form_response');
add_action('wp_enqueue_scripts', 'enqueue_form_deps');
add_action('admin_menu', 'bmaw_options_page');
add_action('admin_enqueue_scripts', 'bmaw_admin_scripts');
add_action('admin_init',  'bmaw_register_setting');
add_shortcode('bmaw-meeting-update-form', 'meeting_update_form');
add_filter('plugin_action_links', 'add_plugin_link', 10, 2);

function array_sanitize_callback($args)
{
    dbg('array sanitise called' . $args);
    return $args;
}

function editor_sanitize_callback($args)
{
    dbg("called editor sanitize");
    return $args;
}

function bmaw_register_setting()
{

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_service_committee_option_array',
        array(
            'type' => 'array',
            'description' => 'bmaw service committee array',
            'sanitize_callback' => 'array_sanitize_callback',
            'show_in_rest' => false,
            'default' => array(
                "0" => array("name" => "Committee1", "e1" => "email 1", "e2" => "email 1.1"),
                "1" => array("name" => "Committee2", "e1" => "email 2", "e2" => "email 2.1"),
            )
        )
    );
    // https://na.org.au/main_server

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_bmlt_server_address',
        array(
            'type' => 'array',
            'description' => 'bmlt server address',
            'sanitize_callback' => 'array_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'https://na.org.au/main_server'
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_email_from_address',
        array(
            'type' => 'string',
            'description' => 'Email from address',
            'sanitize_callback' => 'array_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'example@example'
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_new_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_new_meeting_template',
            'sanitize_callback' => 'editor_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_new_meeting_email_template.html')
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_existing_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_existing_meeting_template',
            'sanitize_callback' => 'editor_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_existing_meeting_email_template.html')

        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_other_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_other_meeting_template',
            'sanitize_callback' => 'editor_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(plugin_dir_url(__FILE__) . 'templates/default_other_meeting_email_template.html')
        )
    );
    add_settings_section(
        'bmaw-settings-section-id',
        '',
        '',
        'bmaw-settings'
    );

    // bmaw_bmlt_address

    add_settings_field(
        'bmaw_bmlt_address',
        'BMLT Server Address',
        'bmaw_bmlt_server_address_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_shortcode_unused',
        'Meeting Update Form Shortcode',
        'bmaw_shortcode_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_service_committee_option_array',
        'Service Committee Configuration',
        'bmaw_service_committee_table_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_email_from_address',
        'Email From Address',
        'bmaw_email_from_address_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_new_meeting_template',
        'Email Template for New Meeting',
        'bmaw_new_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_existing_meeting_template',
        'Email Template for Existing Meeting',
        'bmaw_existing_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_other_meeting_template',
        'Email Template for Other Meeting Update',
        'bmaw_other_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );
}

function bmaw_bmlt_server_address_html()
{
    $bmaw_bmlt_server_address = get_option('bmaw_bmlt_server_address');
    dbg("print html - " . $bmaw_bmlt_server_address);
    echo <<<END
    <div class="bmaw_info_text">
    <br>Your BMLT server address, used to populate the meeting list for meeting changes and closures. For example: <code>https://na.org.au/main_server/</code>
    <br><br>
    </div>
    END;

    echo '<br><label for="bmaw_bmlt_server_address"><b>Server Address:</b></label><input type="url" size="50" id="bmaw_bmlt_server_address" name="bmaw_bmlt_server_address" value="' . $bmaw_bmlt_server_address . '"/>';
    echo '<button type="button" id="bmaw_test_bmlt_server">Test Server Address</button>';
    echo '<br><br>';
}

function bmaw_shortcode_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>You can use the shortcode <code>[bmaw-meeting-update-form service_areas=1,2,3,..]</code> to list the appropriate meetings and service areas in your update form.
    <br><br>
    </div>
    END;
}

function bmaw_email_from_address_html()
{
    $from_address = get_option('bmaw_email_from_address');
    echo <<<END
    <div class="bmaw_info_text">
    <br>The sender (From:) address of meeting update notification emails. Can contain a display name and email in the form <code>Display Name &lt;example@example.com&gt;</code> or just a standard email address.
    <br><br>
    </div>
    END;


    echo '<br><label for="bmaw_email_from_address"><b>From Address:</b></label><input type="text" size="50" name="bmaw_email_from_address" value="' . $from_address . '"/>';
    echo '<br><br>';
}

function bmaw_new_meeting_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing meeting admins about request to create a new meeting.
    <br><br>
    </div>
    END;
    // echo "<p>This template will be used when emailing meeting admins about request to create a new meeting</p>";
    $content = get_option('bmaw_new_meeting_template');
    $editor_id = 'bmaw_new_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<br><br>';
}

function bmaw_existing_meeting_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing meeting admins about an 'other' change type.
    <br><br>
    </div>
    END;

    // echo "<p>This template will be used when emailing meeting admins about a change to an existing meeting</p>";
    $content = get_option('bmaw_existing_meeting_template');
    $editor_id = 'bmaw_existing_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<br><br>';
}

function bmaw_other_meeting_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing meeting admins about an 'other' change type.
    <br><br>
    </div>
    END;
    $content = get_option('bmaw_other_meeting_template');
    $editor_id = 'bmaw_other_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<br><br>';
}

function bmaw_service_committee_table_html()
{

    // dbg("printing the text field");
    $arr = get_option('bmaw_service_committee_option_array');

    echo <<<END
    <div class="bmaw_info_text">
    <br>Configure your service committee contact details here.
    <br>
    <br><b>Service Area</b>: The name as appears in the service area listing on the meeting form.
    <br><b>To/CC Addresses</b>: A comma seperated list of addresses to send the meeting update notification. {field:email_address} can be used to contact the form submitter.
    <br><br>
    </div>
    <table class="committeetable" id="bmaw-service-committee-table">
        <thead>
            <tr>
                <th>Service Area</th>
                <th>To Address(es)</th>
                <th>CC Address(es)</th>
                <th></th>
            </tr>
        </thead>
    <tbody>
    END;
    $i = 0;
    foreach ($arr as $key => $value) {
        echo '<tr>';
        foreach ($value as $k2 => $v2) {
            echo '<td><input type="text" name="bmaw_service_committee_option_array[' . $i . '][' . $k2 . ']" value="' . $v2 . '"/></td>';
        }
        echo '<td><span class="dashicons dashicons-remove" id="bmaw-service-committee-' . $key . '-remove"></span></td></tr>';
        $i++;
    }
    echo '<tr><td></td><td></td><td></td><td><span id="bmaw-service-committee-new-row" class="dashicons dashicons-insert"></span></td></tr>';
    echo '</tbody></table>';
    echo '<br><button type="button" id="bmaw_service_committee_option_array_reload">Reload saved</button>';
    echo '<br><br>';
}

function display_admin_options_page()
{
    ob_start();
    include('admin/admin_options.php');
    $content = ob_get_clean();
    echo $content;
}

function meeting_update_form_response()
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
                    $meeting = json_decode($resp);
                    
                } else {
                    wp_die("meeting id not set");
                }
                if ($reason == "reason_change") {
                    dbg("** change template before");
                    dbg($template);
                    // field substitution
                    $subfields = array(
                        "orig_meeting_name",
                        "orig_duration_time",
                        "orig_start_time",
                        "orig_time_zone",
                        "orig_formats",
                        "orig_weekday",
                        "orig_virtual_meeting_link",
                        "orig_comments"
                    );

                    // Do field replacements in template
                    foreach ($subfields as $field) {
                        $subfield = '{field:' . $field . '}';
                        // strip the orig_
                        $bmlt_field = preg_replace("/^orig_.*/", "", $field);
                        $subwith = $meeting[0][$bmlt_field];
                        $template = str_replace($subfield, $subwith, $template);
                    }
                    dbg("** change template after");
                    dbg($template);
                }
            }

            dbg("** template before");
            dbg($template);
            // field substitution
            $subfields = array(
                "first_name",
                "last_name"
            );

            // Do field replacements in template
            foreach ($subfields as $field) {
                $subfield = '{field:' . $field . '}';
                // $subfield = 'style';
                $subwith = $_POST[$field];
                $template = str_replace($subfield, $subwith, $template);
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

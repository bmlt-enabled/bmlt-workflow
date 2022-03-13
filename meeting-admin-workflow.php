<?php

/**
 * Plugin Name: BMLT Meeting Admin Workflow
 * Plugin URI: https://github.com/nigel-bmlt/meeting-admin-workflow
 * Description: BMLT Meeting Admin Workflow
 * Version: 1.0
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

if (!defined('ABSPATH')) exit; // die if being called directly

define('BMAW_PLUGIN_DIR', plugin_dir_path(__FILE__));
global $bmaw_db_version;
$bmaw_db_version = '1.0';
global $wpdb;
global $bmaw_submissions_table_name;
global $bmaw_service_areas_table_name;
global $bmaw_service_areas_access;

$bmaw_submissions_table_name = $wpdb->prefix . 'bmaw_submissions';
$bmaw_service_areas_table_name = $wpdb->prefix . 'bmaw_service_areas';
$bmaw_service_areas_access = $wpdb->prefix . 'bmaw_service_areas_access';

global $bmaw_capability_manage_submissions;
$bmaw_capability_manage_submissions = 'bmaw_manage_submissions';

include_once 'form handlers/meeting-update-form-handler.php';
include_once 'admin/admin_rest_controller.php';

function meeting_update_form($atts = [], $content = null, $tag = '')
{
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

function deny_cache_register_script($handle, $deps, $name)
{
    wp_register_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
}

function deny_cache_register_style($handle, $deps, $name)
{
    wp_register_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
}

function deny_cache_enqueue_script($handle, $deps, $name)
{
    wp_enqueue_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
}

function deny_cache_enqueue_style($handle, $deps, $name)
{
    wp_enqueue_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
}

function enqueue_form_deps()
{
    wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
    wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
    deny_cache_register_script('bmaw-general-js', array('jquery'), 'js/script_includes.js');
    deny_cache_register_script('bmaw-meeting-update-js', array('jquery', 'jquery.validate'), 'js/meeting_update.js');
    deny_cache_register_style('bmaw-meeting-update-css', array('jquery'), 'css/meeting-update-form.css');
    wp_register_script('jquery.validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js', array('jquery'), '1.0', true);
    wp_register_script('jquery.validate.additional', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js', array('jquery', 'jquery.validate'), '1.0', true);
}

function bmaw_admin_scripts($hook)
{

    if (($hook != 'toplevel_page_bmaw-settings') && ($hook != 'bmaw_page_bmaw-submissions') && ($hook != 'bmaw_page_bmaw-service-areas')) {
        return;
    }

    deny_cache_enqueue_style('bmaw-admin-css', false, 'css/admin_page.css');
    deny_cache_enqueue_script('bmawjs', array('jquery'), 'js/script_includes.js');

    switch ($hook)
    {

        case ('bmaw_page_bmaw-submissions'):
            wp_enqueue_style('thickbox');
            wp_enqueue_script('plugin-install');
            deny_cache_enqueue_script('admin_submissions_js', array('jquery'), 'js/admin_submissions.js');
            deny_cache_enqueue_style('bmaw-admin-submissions-css', false, 'css/admin_submissions.css');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');

            // make sure our rest url is populated
            $script  = 'bmaw_admin_submissions_rest_url = '. json_encode(get_rest_url() . 'bmaw-submission/v1/submissions/') . '; ';
            wp_add_inline_script('admin_submissions_js', $script, 'before');
            break;    
        case ('bmaw_page_bmaw-service-areas'):
            wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
            wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);   
            wp_enqueue_style('select2css');
            wp_enqueue_script('select2');
     
            deny_cache_enqueue_script('admin_service_areas_js', array('jquery'), 'js/admin_service_areas.js');
            // make sure our rest url is populated
            $script  = 'bmaw_admin_bmaw_service_areas_rest_url = '. json_encode(get_rest_url() . 'bmaw-submission/v1/serviceareas') . '; ';
            wp_add_inline_script('admin_service_areas_js', $script, 'before');
            break;
        default:
            deny_cache_enqueue_script('admin_options_js', array('jquery'), 'js/admin_options.js');

    }
}

function bmaw_menu_pages()
{
    add_menu_page(
        'BMAW',
        'BMAW',
        'manage_options',
        'bmaw-settings',
        '',
        'dashicons-analytics',
        null
    );

    add_submenu_page(
        'bmaw-settings',
        'BMAW Settings',
        'BMAW Settings',
        'manage_options',
        'bmaw-settings',
        'display_bmaw_admin_options_page',
        2
    );

    add_submenu_page(
        'bmaw-settings',
        'BMAW Submissions',
        'BMAW Submissions',
        'manage_options',
        'bmaw-submissions',
        'display_bmaw_admin_submissions_page',
        2
    );

    add_submenu_page(
        'bmaw-settings',
        'BMAW Service Areas',
        'BMAW Service Areas',
        'manage_options',
        'bmaw-service-areas',
        'display_bmaw_admin_service_areas_page',
        2
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

// actions, shortcodes, menus and filters
add_action('admin_post_nopriv_meeting_update_form_response', 'meeting_update_form_handler');
add_action('admin_post_meeting_update_form_response', 'meeting_update_form_handler');
add_action('wp_enqueue_scripts', 'enqueue_form_deps');
add_action('admin_menu', 'bmaw_menu_pages');
add_action('admin_enqueue_scripts', 'bmaw_admin_scripts');
add_action('admin_init',  'bmaw_register_setting');
add_action('rest_api_init', 'bmaw_submissions_controller');
add_shortcode('bmaw-meeting-update-form', 'meeting_update_form');
add_filter('plugin_action_links', 'add_plugin_link', 10, 2);

register_activation_hook(__FILE__, 'bmaw_install');
register_deactivation_hook(__FILE__, 'bmaw_uninstall');

function array_sanitize_callback($args)
{
    return $args;
}

function editor_sanitize_callback($args)
{
    return $args;
}

function string_sanitize_callback($args)
{
    return $args;
}

function bmaw_register_setting()
{
    if ((defined('DOING_AJAX') && DOING_AJAX) || (strpos($_SERVER['SCRIPT_NAME'], 'admin-post.php'))) {
        return;
    }

    if (!current_user_can('activate_plugins')) {
        wp_die("This page cannot be accessed");
    }

    register_setting(
        'bmaw-settings-group',
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

    register_setting(
        'bmaw-settings-group',
        'bmaw_bmlt_server_address',
        array(
            'type' => 'array',
            'description' => 'bmlt server address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'https://na.org.au/main_server'
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_bmlt_username',
        array(
            'type' => 'array',
            'description' => 'bmlt automation username',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => ''
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_bmlt_password',
        array(
            'type' => 'array',
            'description' => 'bmlt automation password',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'https://na.org.au/main_server'
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_email_from_address',
        array(
            'type' => 'string',
            'description' => 'Email from address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'example@example'
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_new_meeting_template',
        array(
            'type' => 'string',
            'description' => 'bmaw_new_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html')
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_existing_meeting_template',
        array(
            'type' => 'string',
            'description' => 'bmaw_existing_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html')

        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_other_meeting_template',
        array(
            'type' => 'string',
            'description' => 'bmaw_other_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html')
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_close_meeting_template',
        array(
            'type' => 'string',
            'description' => 'bmaw_close_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html')
        )
    );


    register_setting(
        'bmaw-settings-group',
        'bmaw_fso_email_template',
        array(
            'type' => 'string',
            'description' => 'bmaw_fso_email_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(BMAW_PLUGIN_DIR . 'templates/default_fso_email_template.html')
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_bmlt_test_status',
        array(
            'type' => 'string',
            'description' => 'bmaw_bmlt_test_status',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'failure'
        )
    );

    register_setting(
        'bmaw-settings-group',
        'bmaw_fso_email_address',
        array(
            'type' => 'string',
            'description' => 'FSO email address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'example@example.example'
        )
    );

    add_settings_section(
        'bmaw-settings-section-id',
        '',
        '',
        'bmaw-settings'
    );

    add_settings_field(
        'bmaw_bmlt_server_address',
        'BMLT Server Address',
        'bmaw_bmlt_server_address_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_bmlt_bot_login',
        'BMLT Automation Login Details',
        'bmaw_bmlt_bot_login_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_shortcode',
        'Meeting Update Form Shortcode',
        'bmaw_shortcode_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_service_committee_table',
        'Service Committee Configuration',
        'bmaw_service_committee_table_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_email_from_address',
        'Email From Address',
        'bmaw_email_from_address_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );


    add_settings_field(
        'bmaw_fso_email_address',
        'Email address for the FSO (Starter Kit Notifications)',
        'bmaw_fso_email_address_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );


    add_settings_field(
        'bmaw_fso_email_template',
        'Email Template for FSO emails (Starter Kit Notifications)',
        'bmaw_fso_email_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_new_meeting_template',
        'Email Template for New Meeting',
        'bmaw_new_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_existing_meeting_template',
        'Email Template for Existing Meeting',
        'bmaw_existing_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_other_meeting_template',
        'Email Template for Other Meeting Update',
        'bmaw_other_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );

    add_settings_field(
        'bmaw_close_meeting_template',
        'Email Template for Close Meeting',
        'bmaw_close_meeting_template_html',
        'bmaw-settings',
        'bmaw-settings-section-id'
    );
}

function bmaw_bmlt_server_address_html()
{
    $bmaw_bmlt_server_address = get_option('bmaw_bmlt_server_address');
    $bmaw_bmlt_test_status = get_option('bmaw_bmlt_test_status', "failure");
    echo <<<END
    <div class="bmaw_info_text">
    <br>Your BMLT server address, used to populate the meeting list for meeting changes and closures. For example: <code>https://na.org.au/main_server/</code>
    <br>Ensure you have used the <b>Test Server</b> button and saved settings before using the shortcode form
    <br><br>
    </div>
    END;

    echo '<br><label for="bmaw_bmlt_server_address"><b>Server Address:</b></label><input type="url" size="50" id="bmaw_bmlt_server_address" name="bmaw_bmlt_server_address" value="' . $bmaw_bmlt_server_address . '"/>';
    echo '<button type="button" id="bmaw_test_bmlt_server">Test Server Address</button><span style="display: none;" id="bmaw_test_yes" class="dashicons dashicons-yes"></span><span style="display: none;" id="bmaw_test_no" class="dashicons dashicons-no"></span>';
    echo '<br><br>';
    echo '<input type="hidden" id="bmaw_bmlt_test_status" name="bmaw_bmlt_test_status" value="' . $bmaw_bmlt_test_status . '"></input>';
}

function bmaw_bmlt_bot_login_html()
{
    $bmaw_bmlt_username = get_option('bmaw_bmlt_username');
    $bmaw_bmlt_test_login_status = get_option('bmaw_bmlt_test_login_status', "failure");

    echo <<<END
    <div class="bmaw_info_text">
    <br>Username and password for BMLT automation
    <br><br>
    </div>
    END;
    echo '<br><label for="bmaw_bmlt_server_address"><b>BMLT Username:</b></label><input type="text" size="10" id="bmaw_bmlt_username" name="bmaw_bmlt_username" value="' . $bmaw_bmlt_username . '"/>';
    echo '<br><label for="bmaw_bmlt_server_address"><b>BMLT Password:</b></label><input type="password" size="10" id="bmaw_bmlt_password" name="bmaw_bmlt_password"/>';
    echo '<button type="button" id="bmaw_test_bmlt_server_login">Test Login</button><span style="display: none;" id="bmaw_test_login_yes" class="dashicons dashicons-yes"></span><span style="display: none;" id="bmaw_test_login_no" class="dashicons dashicons-no"></span>';
    echo '<br><br>';
    echo '<input type="hidden" id="bmaw_bmlt_test_login_status" name="bmaw_bmlt_test_login_status" value="' . $bmaw_bmlt_test_login_status . '"></input>';
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

function bmaw_fso_email_address_html()
{
    $from_address = get_option('bmaw_fso_email_address');
    echo <<<END
    <div class="bmaw_info_text">
    <br>The email address to notify the FSO that starter kits are required.
    <br><br>
    </div>
    END;

    echo '<br><label for="bmaw_email_from_address"><b>FSO Email Address:</b></label><input type="text" size="50" name="bmaw_fso_email_address" value="' . $from_address . '"/>';
    echo '<br><br>';
}

function bmaw_fso_email_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing the FSO about starter kit requests.
    <br><br>
    </div>
    END;
    $content = get_option('bmaw_fso_email_template');
    $editor_id = 'bmaw_fso_email_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
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
    $content = get_option('bmaw_new_meeting_template');
    $editor_id = 'bmaw_new_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function bmaw_existing_meeting_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing meeting admins about a change to an existing meeting.
    <br><br>
    </div>
    END;
    $content = get_option('bmaw_existing_meeting_template');
    $editor_id = 'bmaw_existing_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
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
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function bmaw_close_meeting_template_html()
{
    echo <<<END
    <div class="bmaw_info_text">
    <br>This template will be used when emailing meeting admins about closing a meeting.
    <br><br>
    </div>
    END;
    $content = get_option('bmaw_close_meeting_template');
    $editor_id = 'bmaw_close_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    // echo '<br><button type="button" id="bmaw_close_meeting_template_reload">Copy default template to clipboard</button>';
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function bmaw_service_committee_table_html()
{

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
        $j = 0;
        $required = 'required';
        foreach ($value as $k2 => $v2) {
            if ($j == 2) {
                // just the first two fields are required
                $required = "";
            }
            $j++;
            echo '<td><input type="text" name="bmaw_service_committee_option_array[' . $i . '][' . $k2 . ']" value="' . $v2 . '" ' . $required . '/></td>';
        }
        echo '<td><span class="dashicons dashicons-remove" id="bmaw-service-committee-' . $key . '-remove"></span></td></tr>';
        $i++;
    }
    echo '<tr><td></td><td></td><td></td><td><span id="bmaw-service-committee-new-row" class="dashicons dashicons-insert"></span></td></tr>';
    echo '</tbody></table>';
    // echo '<br><button type="button" id="bmaw_service_committee_option_array_reload">Reload saved</button>';
    echo '<br><br>';
}

function display_bmaw_admin_options_page()
{
    $content = '';
    ob_start();
    include('admin/admin_options.php');
    $content = ob_get_clean();
    echo $content;
}

function display_bmaw_admin_submissions_page()
{
    $content = '';
    ob_start();
    include('admin/admin_submissions.php');
    $content = ob_get_clean();
    echo $content;
}

function display_bmaw_admin_service_areas_page()
{
    $content = '';
    ob_start();
    include('admin/admin_service_areas.php');
    $content = ob_get_clean();
    echo $content;
}

function bmaw_install()
{
    global $wpdb;
    global $bmaw_db_version;
    global $bmaw_submissions_table_name;
    global $bmaw_service_areas_table_name;

    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE " . $bmaw_submissions_table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		change_time datetime DEFAULT '0000-00-00 00:00:00',
        changed_by varchar(10),
        change_made varchar(10),
		submitter_name tinytext NOT NULL,
		submission_type tinytext NOT NULL,
        submitter_email varchar(320) NOT NULL,
        meeting_id bigint(20) unsigned,
        changes_requested varchar(1024),
		PRIMARY KEY (id)
	) $charset_collate;";

    dbDelta($sql);

    $sql = "CREATE TABLE " . $bmaw_service_areas_table_name . " (
		service_area_id mediumint(9) NOT NULL ,
        show_on_form bool,
		PRIMARY KEY (service_area_id)
	) $charset_collate;";

    dbDelta($sql);

    $sql = "CREATE TABLE " . $bmaw_service_areas_access . " (
		service_area_id mediumint(9) NOT NULL ,
        wp_uid bigint(20) unsigned  NOT NULL ,
		FOREIGN KEY (service_area_id) REFERENCES " . $bmaw_service_areas_table_name . "(service_area_id) 
	) $charset_collate;";

    dbDelta($sql);

    add_option('bmaw_db_version', $bmaw_db_version);

    // add custom capability to any editable role that contains read capability already
    global $bmaw_capability_manage_submissions;
    error_log("adding capabilities");
    $roles = get_editable_roles();
    error_log(vdump($roles));
    foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
        if (isset($roles[$key]) && $role->has_cap('read')) {
            error_log("adding cap to role");
            error_log(vdump($role));
            // add it but dont grant it yet
            $role->add_cap($bmaw_capability_manage_submissions,false);
        }
    }
    // add a custom role just for trusted servants
    add_role( 'bmaw_trusted_servant', 'BMAW Trusted Servant', array( $bmaw_capability_manage_submissions => true ) );
}

function bmaw_uninstall()
{
    // remove custom capability
    global $bmaw_capability_manage_submissions;
    error_log("deleting capabilities");

    $roles = get_editable_roles();
    foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
        if (isset($roles[$key]) && $role->has_cap($bmaw_capability_manage_submissions)) {
            $role->remove_cap($bmaw_capability_manage_submissions);
        }
    }
}

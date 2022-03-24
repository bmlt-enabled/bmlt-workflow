<?php

/**
 * Plugin Name: Wordpress BMLT Workflow
 * Plugin URI: https://github.com/nigel-bmlt/meeting-admin-workflow
 * Description: Wordpress BMLT Workflow
 * Version: 0.2.0
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

if (!defined('ABSPATH')) exit; // die if being called directly

define('WBW_PLUGIN_DIR', plugin_dir_path(__FILE__));
global $wbw_db_version;
$wbw_db_version = '1.0';
global $wpdb;
global $wbw_submissions_table_name;
global $wbw_service_bodies_table_name;
global $wbw_service_bodies_access_table_name;
global $wbw_rest_namespace;

// our rest namespace
$wbw_rest_namespace = 'wbw/v1';

// placeholder for an 'other' service body
define('CONST_OTHER_SERVICE_BODY','99999999999');

$wbw_submissions_table_name = $wpdb->prefix . 'wbw_submissions';
$wbw_service_bodies_table_name = $wpdb->prefix . 'wbw_service_bodies';
$wbw_service_bodies_access_table_name = $wpdb->prefix . 'wbw_service_bodies_access';

global $wbw_capability_manage_submissions;
$wbw_capability_manage_submissions = 'wbw_manage_submissions';

include_once 'admin/meeting_update_form_handler.php';
include_once 'admin/admin_rest_controller.php';

function meeting_update_form($atts = [], $content = null, $tag = '')
{
    global $wbw_rest_namespace;

    prevent_cache_enqueue_script('wbw-meeting-update-form-js',array('jquery'), 'js/meeting_update_form.js');
    prevent_cache_enqueue_script('wbw-general-js',array('jquery'), 'js/script_includes.js');
    // prevent_cache_enqueue_style('wbw-meeting-update-form-css',array('jquery', 'jquery.validate'), 'js/meeting_update_form.js');
    wp_enqueue_style('wbw-meeting-update-form-css');
    wp_enqueue_script('jquery-validate');
    wp_enqueue_script('jquery-validate-additional');
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
    $script  = 'var wbw_form_submit = ' . json_encode($wbw_rest_namespace.'/submissions') . '; ';
    $script .= 'var wbw_admin_wbw_service_bodies_rest_route = ' . json_encode($wbw_rest_namespace.'/servicebodies') . '; ';
    $script .= 'var wp_rest_base = ' . json_encode(get_rest_url()) . '; ';
    $script .= 'var wbw_bmlt_server_address = "' . get_option('wbw_bmlt_server_address') . '";';
    error_log("adding script ".$script);
    $status = wp_add_inline_script('wbw-meeting-update-form-js', $script, 'before');


    $result = [];
    $result['scripts'] = [];
    $result['styles'] = [];

    // Print all loaded Scripts
    global $wp_scripts;
    foreach( $wp_scripts->queue as $script ) :
       $result['scripts'][] =  $wp_scripts->registered[$script]->src . ";";
    endforeach;

    // Print all loaded Styles (CSS)
    global $wp_styles;
    foreach( $wp_styles->queue as $style ) :
       $result['styles'][] =  $wp_styles->registered[$style]->src . ";";
    endforeach;

    error_log(vdump($result));

    ob_start();
    include('public/meeting_update.php');
    $content .= ob_get_clean();
    return $content;
}

function prevent_cache_register_script($handle, $deps, $name)
{
    wp_register_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
}

function prevent_cache_register_style($handle, $deps, $name)
{
    $ret = wp_register_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
    error_log("register style");
    error_log(vdump($ret));
}

function prevent_cache_enqueue_script($handle, $deps, $name)
{
    $ret = wp_enqueue_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
    error_log("enqueue style ".$handle);
    error_log(vdump($ret));
}

function prevent_cache_enqueue_style($handle, $deps, $name)
{
    $ret = wp_enqueue_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
    error_log("enqueue style ".$handle);
    error_log(vdump($ret));

}

function enqueue_form_deps()
{
    global $wbw_rest_namespace;

    wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
    wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
    prevent_cache_register_script('wbw-general-js', array('jquery'), 'js/script_includes.js');
    prevent_cache_register_script('wbw-meeting-update-form-js', array('jquery', 'jquery.validate'), 'js/meeting_update_form.js');
    prevent_cache_register_style('wbw-meeting-update-form-css', array('jquery'), 'css/meeting_update_form.css');
    wp_register_script('jquery.validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js', array('jquery'), '1.0', true);
    wp_register_script('jquery.validate.additional', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js', array('jquery', 'jquery.validate'), '1.0', true);

    error_log("scripts and styles registered");
}

function wbw_admin_scripts($hook)
{
    global $wbw_rest_namespace;

        // error_log($hook);

    if (($hook != 'toplevel_page_wbw-settings') && ($hook != 'bmlt-workflow_page_wbw-submissions') && ($hook != 'bmlt-workflow_page_wbw-service-bodies')) {
        return;
    }

    prevent_cache_enqueue_style('wbw-admin-css', false, 'css/admin_page.css');
    prevent_cache_enqueue_script('wbwjs', array('jquery'), 'js/script_includes.js');

    switch ($hook) {

        case ('toplevel_page_wbw-settings'):
            // error_log('inside hook');

            // clipboard
            wp_register_script('clipboard', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.js', array('jquery'), '1.0', true);
            wp_enqueue_script('clipboard');

            prevent_cache_enqueue_script('admin_options_js', array('jquery'), 'js/admin_options.js');
            // inline scripts
            $script  = 'var wbw_admin_bmltserver_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/bmltserver') . '; ';

            $arr = get_option('wbw_service_committee_option_array');
            $js_array = json_encode($arr);
            $test_result = get_option('wbw_bmlt_test_status', 'failure');
            $script  .= 'var wbw_service_form_array = ' . $js_array . '; ';
            $script  .= 'var test_status = "' . $test_result . '"; ';

            wp_add_inline_script('admin_options_js', $script, 'before');
            break;
        case ('bmlt-workflow_page_wbw-submissions'):
            prevent_cache_enqueue_script('admin_submissions_js', array('jquery'), 'js/admin_submissions.js');
            prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_submissions.css');
            // jquery dialogs
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            // datatables
            wp_register_style('dtcss', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.css', false, '1.0', 'all');
            wp_register_script('dt', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.js', array('jquery'), '1.0', true);
            wp_enqueue_style('dtcss');
            wp_enqueue_script('dt');
            // select2 for quick editor
            wp_register_style('select2css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
            wp_register_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
            wp_enqueue_style('select2css');
            wp_enqueue_script('select2');


            // make sure our rest url is populated
            $script  = 'var wbw_admin_submissions_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/submissions/') . '; ';
            // add our bmlt server for the submission lookups
            $script .= 'var wbw_bmlt_server_address = "' . get_option('wbw_bmlt_server_address') . '";';

            // add meeting formats
            $bmlt_integration = new BMLTIntegration;
            $formatarr = $bmlt_integration->getMeetingFormats();
            $script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

            // do a one off lookup for our servicebodies
            $url = '/' . $wbw_rest_namespace . '/servicebodies';
            
            $request  = new WP_REST_Request('GET', $url);
            $response = rest_do_request($request);
            $result     = rest_get_server()->response_to_data($response, true);
            // error_log("result = ".vdump($result));
            $script .= 'var wbw_admin_wbw_service_bodies = ' . json_encode($result) . '; ';

            wp_add_inline_script('admin_submissions_js', $script, 'before');
            break;
        case ('bmlt-workflow_page_wbw-service-bodies'):
            wp_register_style('select2css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
            wp_register_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
            wp_enqueue_style('select2css');
            wp_enqueue_script('select2');

            prevent_cache_enqueue_script('admin_service_bodies_js', array('jquery'), 'js/admin_service_bodies.js');
            prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_service_bodies.css');

            // make sure our rest url is populated
            $script  = 'var wbw_admin_wbw_service_bodies_rest_route = ' . json_encode($wbw_rest_namespace.'/servicebodies') . '; ';
            $script .= 'var wp_rest_base = ' . json_encode(get_rest_url()) . '; ';
            wp_add_inline_script('admin_service_bodies_js', $script, 'before');
            break;
    }
}

function wbw_menu_pages()
{
    global $wbw_capability_manage_submissions;

    add_menu_page(
        'BMLT Workflow',
        'BMLT Workflow',
        'manage_options',
        'wbw-settings',
        '',
        'dashicons-analytics',
        null
    );

    add_submenu_page(
        'wbw-settings',
        'Configuration',
        'Configuration',
        'manage_options',
        'wbw-settings',
        'display_wbw_admin_options_page',
        2
    );

    add_submenu_page(
        'wbw-settings',
        'Workflow Submissions',
        'Workflow Submissions',
        $wbw_capability_manage_submissions,
        'wbw-submissions',
        'display_wbw_admin_submissions_page',
        2
    );

    add_submenu_page(
        'wbw-settings',
        'Service Bodies',
        'Service Bodies',
        'manage_options',
        'wbw-service-bodies',
        'display_wbw_admin_service_bodies_page',
        2
    );
}

function add_plugin_link($plugin_actions, $plugin_file)
{

    $new_actions = array();
    if (basename(plugin_dir_path(__FILE__)) . '/wordpress-bmlt-workflow.php' === $plugin_file) {
        $new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'comment-limiter'), esc_url(admin_url('admin.php?page=wbw-settings')));
    }

    return array_merge($new_actions, $plugin_actions);
}

// actions, shortcodes, menus and filters
add_action('admin_post_nopriv_meeting_update_form_response', 'meeting_update_form_handler');
add_action('admin_post_meeting_update_form_response', 'meeting_update_form_handler');
add_action('wp_enqueue_scripts', 'enqueue_form_deps');
add_action('admin_menu', 'wbw_menu_pages');
add_action('admin_enqueue_scripts', 'wbw_admin_scripts');
add_action('admin_init',  'wbw_register_setting');
add_action('rest_api_init', 'wbw_submissions_controller');
add_shortcode('wbw-meeting-update-form', 'meeting_update_form');
add_filter('plugin_action_links', 'add_plugin_link', 10, 2);

register_activation_hook(__FILE__, 'wbw_install');
register_deactivation_hook(__FILE__, 'wbw_uninstall');

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

function wbw_register_setting()
{
    if ((defined('DOING_AJAX') && DOING_AJAX) || (strpos($_SERVER['SCRIPT_NAME'], 'admin-post.php'))) {
        return;
    }

    global $wbw_capability_manage_submissions;

    if ((!current_user_can('activate_plugins'))&&(!current_user_can($wbw_capability_manage_submissions))) {
        wp_die("This page cannot be accessed");
    }

    register_setting(
        'wbw-settings-group',
        'wbw_bmlt_server_address',
        array(
            'type' => 'string',
            'description' => 'bmlt server address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => ''
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_bmlt_username',
        array(
            'type' => 'string',
            'description' => 'bmlt automation username',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => ''
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_bmlt_password',
        array(
            'type' => 'string',
            'description' => 'bmlt automation password',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => ''
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_email_from_address',
        array(
            'type' => 'string',
            'description' => 'Email from address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'example@example'
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_new_meeting_template',
        array(
            'type' => 'string',
            'description' => 'wbw_new_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_new_meeting_email_template.html')
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_existing_meeting_template',
        array(
            'type' => 'string',
            'description' => 'wbw_existing_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_existing_meeting_email_template.html')

        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_other_meeting_template',
        array(
            'type' => 'string',
            'description' => 'wbw_other_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_other_meeting_email_template.html')
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_close_meeting_template',
        array(
            'type' => 'string',
            'description' => 'wbw_close_meeting_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_close_meeting_email_template.html')
        )
    );


    register_setting(
        'wbw-settings-group',
        'wbw_fso_email_template',
        array(
            'type' => 'string',
            'description' => 'wbw_fso_email_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_fso_email_template.html')
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_bmlt_test_status',
        array(
            'type' => 'string',
            'description' => 'wbw_bmlt_test_status',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'failure'
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_fso_email_address',
        array(
            'type' => 'string',
            'description' => 'FSO email address',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'example@example.example'
        )
    );

    add_settings_section(
        'wbw-settings-section-id',
        '',
        '',
        'wbw-settings'
    );

    add_settings_field(
        'wbw_bmlt_server_address',
        'BMLT Server Address',
        'wbw_bmlt_server_address_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_shortcode',
        'Meeting Update Form Shortcode',
        'wbw_shortcode_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );


    add_settings_field(
        'wbw_email_from_address',
        'Email From Address',
        'wbw_email_from_address_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );


    add_settings_field(
        'wbw_fso_email_address',
        'Email address for the FSO (Starter Kit Notifications)',
        'wbw_fso_email_address_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );


    add_settings_field(
        'wbw_fso_email_template',
        'Email Template for FSO emails (Starter Kit Notifications)',
        'wbw_fso_email_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_new_meeting_template',
        'Email Template for New Meeting',
        'wbw_new_meeting_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_existing_meeting_template',
        'Email Template for Existing Meeting',
        'wbw_existing_meeting_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_other_meeting_template',
        'Email Template for Other Meeting Update',
        'wbw_other_meeting_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_close_meeting_template',
        'Email Template for Close Meeting',
        'wbw_close_meeting_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );
}

function wbw_bmlt_server_address_html()
{
    $wbw_bmlt_server_address = get_option('wbw_bmlt_server_address');
    $wbw_bmlt_test_status = get_option('wbw_bmlt_test_status', "failure");
    $wbw_bmlt_username = get_option('wbw_bmlt_username');

    echo <<<END
    <div class="wbw_info_text">
    <br>Your BMLT server address, and a configured BMLT username and password.
    <br><br>Server address is used to populate the meeting list for meeting changes and closures. For example: <code>https://na.test.zzz/main_server/</code>
    <br><br>The BMLT Username and Password is used to action meeting approvals/rejections as well as perform any BMLT related actions on the Wordpress users behalf. This user must be configured as a service body administrator and have access within BMLT to edit any service bodies that are used in WBW form submissions.
    <br><br>Ensure you have used the <b>Test Server</b> button and saved settings before using the shortcode form
    <br><br>
    </div>
    END;

    echo '<br><label for="wbw_bmlt_server_address"><b>Server Address:</b></label><input type="url" size="50" id="wbw_bmlt_server_address" name="wbw_bmlt_server_address" value="' . $wbw_bmlt_server_address . '"/>';
    echo '<br><label for="wbw_bmlt_username"><b>BMLT Username:</b></label><input type="text" size="50" id="wbw_bmlt_username" name="wbw_bmlt_username" value="' . $wbw_bmlt_username . '"/>';
    echo '<br><label for="wbw_bmlt_password"><b>BMLT Password:</b></label><input type="password" size="50" id="wbw_bmlt_password" name="wbw_bmlt_password"/>';
    echo '<button type="button" id="wbw_test_bmlt_server">Test BMLT Configuration</button><span style="display: none;" id="wbw_test_yes" class="dashicons dashicons-yes"></span><span style="display: none;" id="wbw_test_no" class="dashicons dashicons-no"></span>';
    echo '<br><br>';
    echo '<input type="hidden" id="wbw_bmlt_test_status" name="wbw_bmlt_test_status" value="' . $wbw_bmlt_test_status . '"></input>';
}


function wbw_shortcode_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>You can use the shortcode <code>[wbw-meeting-update-form]</code> to list the appropriate meetings and service areas in your update form.
    <br><br>
    </div>
    END;
}

function wbw_email_from_address_html()
{
    $from_address = get_option('wbw_email_from_address');
    echo <<<END
    <div class="wbw_info_text">
    <br>The sender (From:) address of meeting update notification emails. Can contain a display name and email in the form <code>Display Name &lt;example@example.com&gt;</code> or just a standard email address.
    <br><br>
    </div>
    END;

    echo '<br><label for="wbw_email_from_address"><b>From Address:</b></label><input type="text" size="50" name="wbw_email_from_address" value="' . $from_address . '"/>';
    echo '<br><br>';
}

function wbw_fso_email_address_html()
{
    $from_address = get_option('wbw_fso_email_address');
    echo <<<END
    <div class="wbw_info_text">
    <br>The email address to notify the FSO that starter kits are required.
    <br><br>
    </div>
    END;

    echo '<br><label for="wbw_email_from_address"><b>FSO Email Address:</b></label><input type="text" size="50" name="wbw_fso_email_address" value="' . $from_address . '"/>';
    echo '<br><br>';
}

function wbw_fso_email_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing the FSO about starter kit requests.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_fso_email_template');
    $editor_id = 'wbw_fso_email_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function wbw_new_meeting_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing meeting admins about request to create a new meeting.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_new_meeting_template');
    $editor_id = 'wbw_new_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function wbw_existing_meeting_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing meeting admins about a change to an existing meeting.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_existing_meeting_template');
    $editor_id = 'wbw_existing_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function wbw_other_meeting_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing meeting admins about an 'other' change type.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_other_meeting_template');
    $editor_id = 'wbw_other_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}

function wbw_close_meeting_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing meeting admins about closing a meeting.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_close_meeting_template');
    $editor_id = 'wbw_close_meeting_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
    // echo '<br><button type="button" id="wbw_close_meeting_template_reload">Copy default template to clipboard</button>';
    echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
    echo '<br><br>';
}


function display_wbw_admin_options_page()
{
    $content = '';
    ob_start();
    include('admin/admin_options.php');
    $content = ob_get_clean();
    echo $content;
}

function display_wbw_admin_submissions_page()
{
    $content = '';
    ob_start();
    include('admin/admin_submissions.php');
    $content = ob_get_clean();
    echo $content;
}

function display_wbw_admin_service_bodies_page()
{
    $content = '';
    ob_start();
    include('admin/admin_service_bodies.php');
    $content = ob_get_clean();
    echo $content;
}

function wbw_install()
{
    global $wpdb;
    global $wbw_db_version;
    global $wbw_submissions_table_name;
    global $wbw_service_bodies_table_name;
    global $wbw_service_bodies_access_table_name;

    $charset_collate = $wpdb->get_charset_collate();

    // require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE " . $wbw_service_bodies_table_name . " (
		service_body_bigint mediumint(9) NOT NULL,
        service_area_name tinytext NOT NULL,
        contact_email varchar(255) NOT NULL default '',
        show_on_form bool,
		PRIMARY KEY (service_body_bigint)
	) $charset_collate;";

    // dbDelta($sql);
    $wpdb->query($sql);

    $sql = "CREATE TABLE " . $wbw_service_bodies_access_table_name . " (
		service_body_bigint mediumint(9) NOT NULL,
        wp_uid bigint(20) unsigned  NOT NULL,
		FOREIGN KEY (service_body_bigint) REFERENCES " . $wbw_service_bodies_table_name . "(service_body_bigint) 
	) $charset_collate;";

    // dbDelta($sql);
    $wpdb->query($sql);

    $sql = "CREATE TABLE " . $wbw_submissions_table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		change_time datetime DEFAULT '0000-00-00 00:00:00',
        changed_by varchar(10),
        change_made varchar(10),
		submitter_name tinytext NOT NULL,
		submission_type tinytext NOT NULL,
        submitter_email varchar(320) NOT NULL,
        meeting_id bigint(20) unsigned,
        service_body_bigint mediumint(9) NOT NULL,
        changes_requested varchar(1024),
        action_message varchar(1024),
		PRIMARY KEY (id),
        FOREIGN KEY (service_body_bigint) REFERENCES " . $wbw_service_bodies_table_name . "(service_body_bigint) 
	) $charset_collate;";

    // dbDelta($sql);
    $wpdb->query($sql);

    add_option('wbw_db_version', $wbw_db_version);

    global $wbw_capability_manage_submissions;

    // give ourself the capability so we are able to see the submission menu
    $user = wp_get_current_user();
    $user->add_cap($wbw_capability_manage_submissions);

    // add a custom role just for trusted servants
    add_role('wbw_trusted_servant', 'BMLT Workflow Trusted Servant');
}

function wbw_uninstall()
{
    global $wpdb;
    global $wbw_submissions_table_name;
    global $wbw_service_bodies_table_name;
    global $wbw_service_bodies_access_table_name;

    // remove custom capability
    global $wbw_capability_manage_submissions;
    // error_log("deleting capabilities");

    $users = get_users();
    foreach ($users as $user) {
        $user->remove_cap($wbw_capability_manage_submissions);
    }

    remove_role('wbw_trusted_servant');
    
    // Fix for production usage
    $sql = "DROP TABLE " . $wbw_service_bodies_access_table_name . ";";
    $wpdb->query($sql);
    $sql = "DROP TABLE " . $wbw_submissions_table_name . ";";
    $wpdb->query($sql);
    $sql = "DROP TABLE " . $wbw_service_bodies_table_name . ";";
    $wpdb->query($sql);

}
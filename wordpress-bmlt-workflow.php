<?php

/**
 * Plugin Name: Wordpress BMLT Workflow
 * Plugin URI: https://github.com/bmlt-enabled/wordpress-bmlt-workflow
 * Description: Wordpress BMLT Workflow
 * Version: 0.3.10
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

if (!defined('ABSPATH')) exit; // die if being called directly

require 'config.php';

if (file_exists('vendor/autoload.php')) {
    // use composer autoload if we're running under phpunit
    include 'vendor/autoload.php';
} else {
    // custom autoloader if not. only autoloads out of src directory

    spl_autoload_register(function (string $class) {
        if (strpos($class, 'wbw\\') === 0)
        {
            $class = str_replace('wbw\\','', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';    
        }
    });
}

use wbw\Debug;
use wbw\BMLT\Integration;
use wbw\REST\Controller;

// debugging options
global $wbw_dbg;
$wbw_dbg = new Debug;

// our rest namespace
global $wbw_rest_namespace;
$wbw_rest_namespace = 'wbw/v1';
global $wbw_submissions_rest_base;
$wbw_submissions_rest_base = 'submissions';
global $wbw_service_bodies_rest_base;
$wbw_service_bodies_rest_base = 'servicebodies';
global $wbw_bmltserver_rest_base;
$wbw_bmltserver_rest_base = 'bmltserver';

// database configuration
global $wpdb;

global $wbw_db_version;
$wbw_db_version = '1.0';

global $wbw_submissions_table_name;
$wbw_submissions_table_name = $wpdb->prefix . 'wbw_submissions';

global $wbw_service_bodies_table_name;
$wbw_service_bodies_table_name = $wpdb->prefix . 'wbw_service_bodies';

global $wbw_service_bodies_access_table_name;
$wbw_service_bodies_access_table_name = $wpdb->prefix . 'wbw_service_bodies_access';

global $wbw_capability_manage_submissions;
$wbw_capability_manage_submissions = 'wbw_manage_submissions';


function meeting_update_form($atts = [], $content = null, $tag = '')
{
    global $wbw_rest_namespace;
    global $wbw_dbg;

    // base css and js for this page
    prevent_cache_enqueue_script('wbw-meeting-update-form-js', array('jquery'), 'js/meeting_update_form.js');
    prevent_cache_enqueue_style('wbw-meeting-update-form-css', false, 'js/meeting_update_form.js');
    wp_enqueue_style('wbw-meeting-update-form-css');
    prevent_cache_enqueue_script('wbw-general-js', array('jquery'), 'js/script_includes.js');

    // jquery validation
    wp_enqueue_script('jquery-validate');
    wp_enqueue_script('jquery-validate-additional');

    // select2
    enqueue_select2();

    // inline scripts
    $script  = 'var wbw_form_submit_url = ' .json_encode(get_rest_url() . $wbw_rest_namespace . '/submissions') . '; ';
    $script .= 'var wbw_admin_wbw_service_bodies_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/servicebodies') . '; ';
    $script .= 'var wbw_bmlt_server_address = "' . get_option('wbw_bmlt_server_address') . '";';
    // optional fields
    $script .= 'var wbw_optional_location_nation = "' . get_option('wbw_optional_location_nation') . '";';
    $script .= 'var wbw_optional_location_sub_province = "' . get_option('wbw_optional_location_sub_province') . '";';

    // add meeting formats
    $bmlt_integration = new Integration;
    $formatarr = $bmlt_integration->getMeetingFormats();
    $wbw_dbg->debug_log("FORMATS");
    $wbw_dbg->debug_log($wbw_dbg->vdump($formatarr));
    $wbw_dbg->debug_log(json_encode($formatarr));
    $script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

    $wbw_dbg->debug_log("adding script " . $script);
    $status = wp_add_inline_script('wbw-meeting-update-form-js', $script, 'before');


    $result = [];
    $result['scripts'] = [];
    $result['styles'] = [];

    // Print all loaded Scripts
    global $wp_scripts;
    foreach ($wp_scripts->queue as $script) :
        $result['scripts'][] =  $wp_scripts->registered[$script]->src . ";";
    endforeach;

    // Print all loaded Styles (CSS)
    global $wp_styles;
    foreach ($wp_styles->queue as $style) :
        $result['styles'][] =  $wp_styles->registered[$style]->src . ";";
    endforeach;

    $wbw_dbg->debug_log($wbw_dbg->vdump($result));

    ob_start();
    include('public/meeting_update_form.php');
    $content .= ob_get_clean();
    return $content;
}

function prevent_cache_register_script($handle, $deps, $name)
{
    wp_register_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
}

function prevent_cache_register_style($handle, $deps, $name)
{
    // global $wbw_dbg;

    $ret = wp_register_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
    // $wbw_dbg->debug_log("register style");
    // $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
}

function prevent_cache_enqueue_script($handle, $deps, $name)
{
    // global $wbw_dbg;

    $ret = wp_enqueue_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
    // $wbw_dbg->debug_log("enqueue style " . $handle);
    // $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
}

function prevent_cache_enqueue_style($handle, $deps, $name)
{
    // global $wbw_dbg;

    $ret = wp_enqueue_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
    // $wbw_dbg->debug_log("enqueue style " . $handle);
    // $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
}

function register_select2()
{
    wp_register_style('select2css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', false, '1.0', 'all');
    wp_register_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '1.0', true);
}

function enqueue_select2()
{
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
}

function enqueue_jquery_dialog()
{
    // jquery dialogs
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
}

function enqueue_form_deps()
{
    global $wbw_dbg;

    register_select2();
    prevent_cache_register_script('wbw-general-js', array('jquery'), 'js/script_includes.js');
    prevent_cache_register_script('wbw-meeting-update-form-js', array('jquery', 'jquery.validate'), 'js/meeting_update_form.js');
    prevent_cache_register_style('wbw-meeting-update-form-css', false, 'css/meeting_update_form.css');
    wp_register_script('jquery.validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js', array('jquery'), '1.0', true);
    wp_register_script('jquery.validate.additional', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js', array('jquery', 'jquery.validate'), '1.0', true);
    wp_enqueue_style( 'dashicons' );
    $wbw_dbg->debug_log("scripts and styles registered");
}

function wbw_admin_scripts($hook)
{
    global $wbw_rest_namespace;
    global $wbw_dbg;

    // $wbw_dbg->debug_log($hook);

    if (($hook != 'toplevel_page_wbw-settings') && ($hook != 'bmlt-workflow_page_wbw-submissions') && ($hook != 'bmlt-workflow_page_wbw-service-bodies')) {
        return;
    }

    prevent_cache_enqueue_script('wbwjs', array('jquery'), 'js/script_includes.js');

    switch ($hook) {

        case ('toplevel_page_wbw-settings'):
            // base css and scripts for this page
            prevent_cache_enqueue_style('wbw-admin-css', false, 'css/admin_options.css');
            prevent_cache_enqueue_script('admin_options_js', array('jquery'), 'js/admin_options.js');

            // clipboard
            wp_enqueue_script('clipboard');

            // jquery dialog
            enqueue_jquery_dialog();

            // inline scripts
            $script  = 'var wbw_admin_bmltserver_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/bmltserver') . '; ';
            $script .= 'var wbw_admin_wbw_service_bodies_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/servicebodies') . '; ';
            $script .= 'var wbw_bmlt_server_address = "' . get_option('wbw_bmlt_server_address'). '"; ';
            wp_add_inline_script('admin_options_js', $script, 'before');
            break;

        case ('bmlt-workflow_page_wbw-submissions'):
            // base css and scripts for this page
            prevent_cache_enqueue_script('admin_submissions_js', array('jquery'), 'js/admin_submissions.js');
            prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_submissions.css');

            // jquery dialog
            enqueue_jquery_dialog();

            // datatables
            wp_register_style('dtcss', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.css', false, '1.0', 'all');
            wp_register_script('dt', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.js', array('jquery'), '1.0', true);
            wp_enqueue_style('dtcss');
            wp_enqueue_script('dt');

            // select2 for quick editor
            register_select2();
            enqueue_select2();

            // make sure our rest urls are populated
            $script  = 'var wbw_admin_submissions_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/submissions/') . '; ';
            $script  .= 'var wbw_bmltserver_geolocate_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/bmltserver/geolocate') . '; ';
            // add our bmlt server for the submission lookups
            $script .= 'var wbw_bmlt_server_address = "' . get_option('wbw_bmlt_server_address') . '";';

            // add meeting formats
            $bmlt_integration = new Integration;
            $formatarr = $bmlt_integration->getMeetingFormats();
            $wbw_dbg->debug_log("FORMATS");
            $wbw_dbg->debug_log($wbw_dbg->vdump($formatarr));
            $wbw_dbg->debug_log(json_encode($formatarr));
            $script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

            // do a one off lookup for our servicebodies
            $url = '/' . $wbw_rest_namespace . '/servicebodies';

            $request  = new WP_REST_Request('GET', $url);
            $response = rest_do_request($request);
            $result     = rest_get_server()->response_to_data($response, true);
            $script .= 'var wbw_admin_wbw_service_bodies = ' . json_encode($result) . '; ';

            // defaults for approve close form
            $wbw_default_closed_meetings = get_option('wbw_delete_closed_meetings');
            $script .= 'var wbw_default_closed_meetings = "' . $wbw_default_closed_meetings . '"; ';

            // optional fields in quickedit
            $script .= 'var wbw_optional_location_nation = "' . get_option('wbw_optional_location_nation') . '";';
            $script .= 'var wbw_optional_location_sub_province = "' . get_option('wbw_optional_location_sub_province') . '";';
        
            wp_add_inline_script('admin_submissions_js', $script, 'before');

            break;

        case ('bmlt-workflow_page_wbw-service-bodies'):
            // base css and scripts for this page
            prevent_cache_enqueue_script('admin_service_bodies_js', array('jquery'), 'js/admin_service_bodies.js');
            prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_service_bodies.css');

            // select2
            register_select2();
            enqueue_select2();

            // make sure our rest url is populated
            $script  = 'var wbw_admin_wbw_service_bodies_rest_url = ' . json_encode(get_rest_url() . $wbw_rest_namespace . '/servicebodies') . '; ';
            $script  .= 'var wp_users_url = ' . json_encode(get_rest_url() . 'vp/v2/users') . '; ';
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

function wbw_rest_controller()
{
    $controller = new Controller();
    $controller->register_routes();
}

// actions, shortcodes, menus and filters
add_action('wp_enqueue_scripts', 'enqueue_form_deps');
add_action('admin_menu', 'wbw_menu_pages');
add_action('admin_enqueue_scripts', 'wbw_admin_scripts');
add_action('admin_init',  'wbw_register_setting');
add_action('rest_api_init', 'wbw_rest_controller');
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

    global $wbw_capability_manage_submissions;

    if ((!current_user_can('activate_plugins')) && (!current_user_can($wbw_capability_manage_submissions))) {
        wp_die("This page cannot be accessed");
    }

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
        'wbw_delete_closed_meetings',
        array(
            'type' => 'string',
            'description' => 'Default for close meeting submission',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'unpublish'
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_optional_location_nation',
        array(
            'type' => 'string',
            'description' => 'optional field for location_nation',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'hidden'
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_optional_location_sub_province',
        array(
            'type' => 'string',
            'description' => 'optional field for location_sub_province',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => 'hidden'
        )
    );

    register_setting(
        'wbw-settings-group',
        'wbw_submitter_email_template',
        array(
            'type' => 'string',
            'description' => 'wbw_submitter_email_template',
            'sanitize_callback' => 'string_sanitize_callback',
            'show_in_rest' => false,
            'default' => file_get_contents(WBW_PLUGIN_DIR . 'templates/default_submitter_email_template.html')
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
        'BMLT Configuration',
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
        'wbw_delete_closed_meetings',
        'Default for close meeting submission',
        'wbw_delete_closed_meetings_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );

    add_settings_field(
        'wbw_optional_form_fields',
        'Optional form fields',
        'wbw_optional_form_fields_html',
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
        'wbw_submitter_email_template',
        'Email Template for New Meeting',
        'wbw_submitter_email_template_html',
        'wbw-settings',
        'wbw-settings-section-id'
    );
}

function wbw_bmlt_server_address_html()
{
    echo '<div id="wbw_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>Your BMLT details are successfully configured.</div>';
    echo '<div id="wbw_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>Your BMLT details are not configured correctly.</div>';
    echo '<br>';
    echo '<button type="button" id="wbw_configure_bmlt_server">Update BMLT Configuration</button>';
    echo '<br>';
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

function wbw_delete_closed_meetings_html()
{
    $selection = get_option('wbw_delete_closed_meetings');
    $delete = '';
    $unpublish = '';
    if ($selection === 'delete') {
        $delete = 'selected';
    } else {
        $unpublish = 'selected';
    }

    echo <<<END
    <div class="wbw_info_text">
    <br>Trusted servants approving a 'Close Meeting' request can choose to either Delete or Unpublish. This option selects the default for all trusted servants.
    <br><br>
    </div>
    END;

    echo '<br><label for="wbw_delete_closed_meetings"><b>Close meeting default:</b></label><select name="wbw_delete_closed_meetings"><option name="unpublish" value="unpublish" ' . $unpublish . '>Unpublish</option><option name="delete" value="delete" ' . $delete . '>Delete</option>';
    echo '<br><br>';
}


function wbw_optional_form_fields_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>Optional form fields, available depending on how your service bodies use BMLT. These can be displayed, displayed and required, or hidden from your end users.
    <br><br>
    </div>
    END;

    do_optional_field('wbw_optional_location_nation', 'Nation');
    do_optional_field('wbw_optional_location_sub_province', 'Sub Province');
}

function do_optional_field($option, $friendlyname)
{
    $value = get_option($option);
    global $wbw_dbg;
    $wbw_dbg->debug_log($wbw_dbg->vdump($value));
    $hidden = '';
    $displayrequired = '';
    $display = '';

    switch ($value) {
        case 'hidden':
            $hidden = 'selected';
            break;
        case 'displayrequired':
            $displayrequired = 'selected';
            break;
        case 'display':
            $display = 'selected';
            break;
    }
    echo <<<END
    <br><label for="${option}"><b>${friendlyname}:</b>
    </label><select name="${option}">
    <option name="hidden" value="hidden" ${hidden}>Hidden</option>
    <option name="displayrequired" value="displayrequired" ${displayrequired}>Display + Required Field</option>
    <option name="display" value="display" ${display}>Display Only</option>
    </select>
    <br><br>
    END;
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

function wbw_submitter_email_template_html()
{
    echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing a submitter about the meeting change they've requested.
    <br><br>
    </div>
    END;
    $content = get_option('wbw_submitter_email_template');
    $editor_id = 'wbw_submitter_email_template';

    wp_editor($content, $editor_id, array('media_buttons' => false));
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
		service_body_bigint bigint(20) NOT NULL,
        service_body_name tinytext NOT NULL,
        service_body_description text,
        contact_email varchar(255) NOT NULL default '',
        show_on_form bool,
		PRIMARY KEY (service_body_bigint)
	) $charset_collate;";

    // dbDelta($sql);
    $wpdb->query($sql);

    $sql = "CREATE TABLE " . $wbw_service_bodies_access_table_name . " (
		service_body_bigint bigint(20) NOT NULL,
        wp_uid bigint(20) unsigned  NOT NULL,
		FOREIGN KEY (service_body_bigint) REFERENCES " . $wbw_service_bodies_table_name . "(service_body_bigint) 
	) $charset_collate;";

    // dbDelta($sql);
    $wpdb->query($sql);

    $sql = "CREATE TABLE " . $wbw_submissions_table_name . " (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		change_time datetime DEFAULT '0000-00-00 00:00:00',
        changed_by varchar(10),
        change_made varchar(10),
		submitter_name tinytext NOT NULL,
		submission_type tinytext NOT NULL,
        submitter_email varchar(320) NOT NULL,
        meeting_id bigint(20) unsigned,
        service_body_bigint bigint(20) NOT NULL,
        changes_requested varchar(2048),
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
    // $wbw_dbg->debug_log("deleting capabilities");

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

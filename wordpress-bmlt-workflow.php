<?php

/**
 * Plugin Name: Wordpress BMLT Workflow
 * Plugin URI: https://github.com/bmlt-enabled/wordpress-bmlt-workflow
 * Description: Workflows for BMLT meeting management!
 * Version: 0.4.4
 * Requires at least: 5.2
 * Tested up to: 6.0
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

 define('WBW_PLUGIN_VERSION','0.4.4');

if (!defined('ABSPATH')) exit; // die if being called directly

require 'config.php';

if (file_exists('vendor/autoload.php')) {
    // use composer autoload if we're running under phpunit
    include 'vendor/autoload.php';
} else {
    // custom autoloader if not. only autoloads out of src directory
    spl_autoload_register(function (string $class) {
        if (strpos($class, 'wbw\\') === 0) {
            $class = str_replace('wbw\\', '', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        }
    });
}

use wbw\BMLT\Integration;
use wbw\REST\Controller;
use wbw\WBW_Database;
use wbw\WBW_WP_Options;
use wbw\WBW_Rest;

// database configuration
global $wpdb;

if (!class_exists('wbw_plugin')) {
    class wbw_plugin
    {
        use \wbw\WBW_Debug;

        public function __construct()
        {
            $this->WBW_WP_Options = new WBW_WP_Options();
            $this->bmlt_integration = new Integration();
            $this->WBW_Rest = new WBW_Rest();
            $this->WBW_Rest_Controller = new Controller();
            $this->WBW_Database = new WBW_Database();

            // actions, shortcodes, menus and filters
            add_action('wp_enqueue_scripts', array(&$this, 'wbw_enqueue_form_deps'));
            add_action('admin_menu', array(&$this, 'wbw_menu_pages'));
            add_action('admin_enqueue_scripts', array(&$this, 'wbw_admin_scripts'));
            add_action('admin_init',  array(&$this, 'wbw_register_setting'));
            add_action('rest_api_init', array(&$this, 'wbw_rest_controller'));
            add_shortcode('wbw-meeting-update-form', array(&$this, 'wbw_meeting_update_form'));
            add_filter('plugin_action_links', array(&$this, 'wbw_add_plugin_link'), 10, 2);
            add_action('user_register', array(&$this,'wbw_add_capability'), 10, 1 );

            // auto updates
            // add_filter( 'pre_set_site_transient_update_plugins', array(&$this,'wbw_plugin_update_check' ));

            register_activation_hook(__FILE__, array(&$this, 'wbw_install'));
            register_deactivation_hook(__FILE__, array(&$this, 'wbw_uninstall'));
        }

        // function wbw_plugin_update_check( $data ) {
            
        //     if ( empty( $data ) ) {
        //         return $data;
        //     }

        //     $url = 'https://raw.githubusercontent.com/bmlt-enabled/wordpress-bmlt-workflow/0.4.3-fixes/releases.json?' . time();

        //     $request = wp_remote_get( $url );

        //     if ( is_wp_error( $request ) ) {
        //         return $data;
        //     }

        //     $json = wp_remote_retrieve_body( $request );
        //     $response = json_decode( $json );
        //     $this->debug_log("got auto update response");
        //     $this->debug_log($response);

        //     if ( ! isset( $response->slug ) || ! isset( $response->new_version ) || ! isset( $response->url ) || ! isset( $response->package ) ) {
        //         return $data;
        //     }

        //     if ( version_compare( WBW_PLUGIN_VERSION, $response->new_version, '<' ) ) {
        //         $data->response[ 'wordpress-bmlt-workflow/wordpress-bmlt-workflow.php' ] = $response;
        //     }

        //     return $data;
        // }

        public function wbw_meeting_update_form($atts = [], $content = null, $tag = '')
        {

            $wbw_bmlt_test_status = get_option('wbw_bmlt_test_status', "failure");
            if ($wbw_bmlt_test_status != "success") {
                wp_die("<h4>WBW Plugin Error: BMLT Server not configured and tested.</h4>");
            }

            // base css and js for this page
            $this->prevent_cache_enqueue_script('wbw-meeting-update-form-js', array('jquery'), 'js/meeting_update_form.js');
            $this->prevent_cache_enqueue_style('wbw-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->prevent_cache_enqueue_script('wbw-general-js', array('jquery'), 'js/script_includes.js');
            wp_enqueue_style('dashicons');

            // jquery validation

            wp_enqueue_script('jqueryvalidate');
            wp_enqueue_script('jqueryvalidateadditional');

            // select2
            $this->enqueue_select2();

            // inline scripts
            $script  = 'var wbw_form_submit_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/submissions') . '; ';
            $script .= 'var wbw_bmlt_server_address = "' . $this->WBW_WP_Options->wbw_get_option('wbw_bmlt_server_address') . '";';
            // optional fields
            $script .= 'var wbw_optional_location_nation = "' . $this->WBW_WP_Options->wbw_get_option('wbw_optional_location_nation') . '";';
            $script .= 'var wbw_optional_location_sub_province = "' . $this->WBW_WP_Options->wbw_get_option('wbw_optional_location_sub_province') . '";';

            // add meeting formats
            $formatarr = $this->bmlt_integration->getMeetingFormats();
            // $this->debug_log("FORMATS");
            // $this->debug_log($formatarr);
            // $this->debug_log(json_encode($formatarr));
            $script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

            // do a one off lookup for our servicebodies
            $url = '/' . $this->WBW_Rest->wbw_rest_namespace . '/servicebodies';
            $this->debug_log("rest url = " . $url);

            $request  = new WP_REST_Request('GET', $url);
            $response = rest_do_request($request);
            $result = rest_get_server()->response_to_data($response, true);
            if (count($result) == 0) {
                wp_die("<h4>WBW Plugin Error: Service bodies not configured.</h4>");
            }
            $script .= 'var wbw_service_bodies = ' . json_encode($result) . '; ';

            $this->debug_log("adding script " . $script);
            $status = wp_add_inline_script('wbw-meeting-update-form-js', $script, 'before');
            $this->prevent_cache_enqueue_script('wbw-meeting-update-form-js', array('jquery'), 'js/meeting_update_form.js');

            $result = [];
            $result['scripts'] = [];
            $result['styles'] = [];

            $this->debug_log("All scripts and styles");

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

            $this->debug_log(($result));


            ob_start();
            include('public/meeting_update_form.php');
            $content .= ob_get_clean();
            return $content;
        }

        private function prevent_cache_register_script($handle, $deps, $name)
        {
            wp_register_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
        }

        private function prevent_cache_register_style($handle, $deps, $name)
        {

            $ret = wp_register_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
        }

        private function prevent_cache_enqueue_script($handle, $deps, $name)
        {

            $ret = wp_enqueue_script($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), true);
        }

        private function prevent_cache_enqueue_style($handle, $deps, $name)
        {

            $ret = wp_enqueue_style($handle, plugin_dir_url(__FILE__) . $name, $deps, filemtime(plugin_dir_path(__FILE__) . $name), 'all');
        }

        private function register_select2()
        {
            wp_register_style('select2css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', false, '1.0', 'all');
            wp_register_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '1.0', true);
        }

        private function enqueue_select2()
        {
            wp_enqueue_style('select2css');
            wp_enqueue_script('select2');
        }

        private function enqueue_jquery_dialog()
        {
            // jquery dialogs
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
        }

        public function wbw_enqueue_form_deps()
        {

            $this->register_select2();
            wp_register_script('jqueryvalidate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js', array('jquery'), '1.0', true);
            wp_register_script('jqueryvalidateadditional', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js', array('jquery', 'jqueryvalidate'), '1.0', true);
            $this->prevent_cache_register_script('wbw-general-js', array('jquery'), 'js/script_includes.js');
            $this->prevent_cache_register_script('wbw-meeting-update-form-js', array('jquery', 'jqueryvalidate', 'jqueryvalidateadditional'), 'js/meeting_update_form.js');
            $this->prevent_cache_register_style('wbw-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->debug_log("scripts and styles registered");
        }

        public function wbw_admin_scripts($hook)
        {


            // $this->debug_log($hook);

            if (($hook != 'toplevel_page_wbw-settings') && ($hook != 'bmlt-workflow_page_wbw-submissions') && ($hook != 'bmlt-workflow_page_wbw-service-bodies')) {
                return;
            }

            $this->prevent_cache_enqueue_script('wbwjs', array('jquery'), 'js/script_includes.js');

            switch ($hook) {

                case ('toplevel_page_wbw-settings'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_style('wbw-admin-css', false, 'css/admin_options.css');
                    $this->prevent_cache_enqueue_script('admin_options_js', array('jquery'), 'js/admin_options.js');

                    // clipboard
                    wp_enqueue_script('clipboard');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // inline scripts
                    $script  = 'var wbw_admin_bmltserver_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/bmltserver') . '; ';
                    $script .= 'var wbw_admin_backup_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/options/backup') . '; ';
                    $script .= 'var wbw_admin_restore_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/options/restore') . '; ';
                    $script .= 'var wbw_admin_wbw_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/servicebodies') . '; ';

                    wp_add_inline_script('admin_options_js', $script, 'before');
                    break;

                case ('bmlt-workflow_page_wbw-submissions'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('admin_submissions_js', array('jquery'), 'js/admin_submissions.js');
                    $this->prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_submissions.css');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // datatables
                    wp_register_style('dtcss', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.css', false, '1.0', 'all');
                    wp_register_script('dt', 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/r-2.2.9/sl-1.3.4/datatables.min.js', array('jquery'), '1.0', true);
                    wp_enqueue_style('dtcss');
                    wp_enqueue_script('dt');

                    // select2 for quick editor
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest urls are populated
                    $script  = 'var wbw_admin_submissions_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/submissions/') . '; ';
                    $script  .= 'var wbw_bmltserver_geolocate_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/bmltserver/geolocate') . '; ';
                    // add our bmlt server for the submission lookups
                    $script .= 'var wbw_bmlt_server_address = "' . $this->WBW_WP_Options->wbw_get_option('wbw_bmlt_server_address') . '";';

                    // add meeting formats
                    $formatarr = $this->bmlt_integration->getMeetingFormats();
                    $this->debug_log("FORMATS");
                    $this->debug_log(($formatarr));
                    $this->debug_log(json_encode($formatarr));
                    $script .= 'var wbw_bmlt_formats = ' . json_encode($formatarr) . '; ';

                    // do a one off lookup for our servicebodies
                    $url = '/' . $this->WBW_Rest->wbw_rest_namespace . '/servicebodies';

                    $request  = new WP_REST_Request('GET', $url);
                    $response = rest_do_request($request);
                    $result     = rest_get_server()->response_to_data($response, true);
                    $script .= 'var wbw_admin_wbw_service_bodies = ' . json_encode($result) . '; ';

                    // defaults for approve close form
                    $wbw_default_closed_meetings = $this->WBW_WP_Options->wbw_get_option('wbw_delete_closed_meetings');
                    $script .= 'var wbw_default_closed_meetings = "' . $wbw_default_closed_meetings . '"; ';

                    // optional fields in quickedit
                    $script .= 'var wbw_optional_location_nation = "' . $this->WBW_WP_Options->wbw_get_option('wbw_optional_location_nation') . '";';
                    $script .= 'var wbw_optional_location_sub_province = "' . $this->WBW_WP_Options->wbw_get_option('wbw_optional_location_sub_province') . '";';

                    wp_add_inline_script('admin_submissions_js', $script, 'before');

                    break;

                case ('bmlt-workflow_page_wbw-service-bodies'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('admin_service_bodies_js', array('jquery'), 'js/admin_service_bodies.js');
                    $this->prevent_cache_enqueue_style('wbw-admin-submissions-css', false, 'css/admin_service_bodies.css');

                    // select2
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest url is populated
                    $script = 'var wbw_admin_wbw_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->WBW_Rest->wbw_rest_namespace . '/servicebodies') . '; ';
                    $script .= 'var wp_users_url = ' . json_encode(get_rest_url() . 'wp/v2/users') . '; ';
                    wp_add_inline_script('admin_service_bodies_js', $script, 'before');
                    break;
            }
        }

        public function wbw_menu_pages()
        {

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
                array(&$this, 'display_wbw_admin_options_page'),
                2
            );

            add_submenu_page(
                'wbw-settings',
                'Workflow Submissions',
                'Workflow Submissions',
                $this->WBW_WP_Options->wbw_capability_manage_submissions,
                'wbw-submissions',
                array(&$this, 'display_wbw_admin_submissions_page'),
                2
            );

            add_submenu_page(
                'wbw-settings',
                'Service Bodies',
                'Service Bodies',
                'manage_options',
                'wbw-service-bodies',
                array(&$this, 'display_wbw_admin_service_bodies_page'),
                2
            );
        }

        public function wbw_add_plugin_link($plugin_actions, $plugin_file)
        {

            $new_actions = array();
            if (basename(plugin_dir_path(__FILE__)) . '/wordpress-bmlt-workflow.php' === $plugin_file) {
                $new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'comment-limiter'), esc_url(admin_url('admin.php?page=wbw-settings')));
            }

            return array_merge($new_actions, $plugin_actions);
        }

        public function wbw_rest_controller()
        {
            $this->WBW_Rest_Controller->register_routes();
        }

        public function wbw_register_setting()
        {

            $this->debug_log("registering settings");

            if (!current_user_can('activate_plugins')) {
                wp_die("This page cannot be accessed");
            }

            register_setting(
                'wbw-settings-group',
                'wbw_email_from_address',
                array(
                    'type' => 'string',
                    'description' => 'Email from address',
                    'sanitize_callback' => array(&$this, 'wbw_email_from_address_sanitize_callback'),
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
                    'sanitize_callback' => array(&$this, 'wbw_delete_closed_meetings_sanitize_callback'),
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
                    'sanitize_callback' => array(&$this, 'wbw_optional_location_nation_sanitize_callback'),
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
                    'sanitize_callback' => array(&$this, 'wbw_optional_location_sub_province_sanitize_callback'),
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
                    'sanitize_callback' => null,
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
                    'sanitize_callback' => null,
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
                    'sanitize_callback' => array(&$this, 'wbw_fso_email_address_sanitize_callback'),
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
                array(&$this, 'wbw_bmlt_server_address_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_backup_restore',
                'Backup and Restore',
                array(&$this, 'wbw_backup_restore_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_shortcode',
                'Meeting Update Form Shortcode',
                array(&$this,'wbw_shortcode_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_email_from_address',
                'Email From Address',
                array(&$this,'wbw_email_from_address_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_delete_closed_meetings',
                'Default for close meeting submission',
                array(&$this, 'wbw_delete_closed_meetings_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_optional_form_fields',
                'Optional form fields',
                array(&$this, 'wbw_optional_form_fields_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_fso_email_address',
                'Email address for the FSO (Starter Kit Notifications)',
                array(&$this, 'wbw_fso_email_address_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );


            add_settings_field(
                'wbw_fso_email_template',
                'Email Template for FSO emails (Starter Kit Notifications)',
                array(&$this, 'wbw_fso_email_template_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );

            add_settings_field(
                'wbw_submitter_email_template',
                'Email Template for New Meeting',
                array(&$this, 'wbw_submitter_email_template_html'),
                'wbw-settings',
                'wbw-settings-section-id'
            );
        }

        public function wbw_optional_location_nation_sanitize_callback($input)
        {
            $this->debug_log("location nation sanitize callback");            
            $this->debug_log($input);

            $output = get_option('wbw_optional_location_nation');
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
                }
            add_settings_error('wbw_optional_location_nation','err','Invalid Nation setting.');
            return $output;
        }

        public function wbw_optional_location_sub_province_sanitize_callback($input)
        {
            $output = get_option('wbw_optional_location_sub_province');
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
                }
            add_settings_error('wbw_optional_location_sub_province','err','Invalid Sub Province setting.');
            return $output;
        }

        public function wbw_email_from_address_sanitize_callback($input)
        {
            $output = get_option('wbw_email_from_address');
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email))
            {
                add_settings_error('wbw_email_from_address','err','Invalid email from address.');
                return $output;
            }
            return $sanitized_email;
        }

        public function wbw_fso_email_address_sanitize_callback($input)
        {
            $output = get_option('wbw_fso_email_address');
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email))
            {
                add_settings_error('wbw_fso_email_address','err','Invalid FSO email address.');
                return $output;
            }
            return $sanitized_email;
        }

        public function wbw_delete_closed_meetings_sanitize_callback($input)
        {
            $output = get_option('wbw_delete_closed_meetings');

            switch ($input) {
                case 'delete':
                case 'unpublish':
                    return $input;
                }
            add_settings_error('wbw_delete_closed_meetings','err','Invalid delete closed meetings  setting.');
            return $output;
        }

        public function wbw_bmlt_server_address_html()
        {
            echo '<div id="wbw_bmlt_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>Your BMLT details are successfully configured.</div>';
            echo '<div id="wbw_bmlt_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>Your BMLT details are not configured correctly.</div>';
            echo '<div id="wbw_servicebodies_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>Your service bodies are successfully configured.</div>';
            echo '<div id="wbw_servicebodies_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>Your service bodies are not configured and saved correctly. <a href="?wbw-submissions">Fix</a></div>';
            echo '<br>';
            echo '<button type="button" id="wbw_configure_bmlt_server">Update BMLT Configuration</button>';
            echo '<br>';
        }

        public function wbw_backup_restore_html()
        {
            echo '<button type="button" id="wbw_backup">Backup Configuration</button>   <button type="button" id="wbw_restore">Restore Configuration</button><input type="file" id="wbw_file_selector" accept=".json,application/json" style="display:none">';
            echo '<span class="spinner" id="wbw-backup-spinner"></span><br>';
        }

        public function wbw_shortcode_html()
        {
            echo <<<END
    <div class="wbw_info_text">
    <br>You can use the shortcode <code>[wbw-meeting-update-form]</code> to list the appropriate meetings and service areas in your update form.
    <br><br>
    </div>
    END;
        }

        public function wbw_email_from_address_html()
        {

            $from_address = $this->WBW_WP_Options->wbw_get_option('wbw_email_from_address');
            echo <<<END
    <div class="wbw_info_text">
    <br>The sender (From:) address of meeting update notification emails. Can contain a display name and email in the form <code>Display Name &lt;example@example.com&gt;</code> or just a standard email address.
    <br><br>
    </div>
    END;

            echo '<br><label for="wbw_email_from_address"><b>From Address:</b></label><input id="wbw_email_from_address" type="text" size="50" name="wbw_email_from_address" value="' . $from_address . '"/>';
            echo '<br><br>';
        }

        public function wbw_delete_closed_meetings_html()
        {

            $selection = $this->WBW_WP_Options->wbw_get_option('wbw_delete_closed_meetings');
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

            echo '<br><label for="wbw_delete_closed_meetings"><b>Close meeting default:</b></label><select id="wbw_delete_closed_meetings" name="wbw_delete_closed_meetings"><option name="unpublish" value="unpublish" ' . $unpublish . '>Unpublish</option><option name="delete" value="delete" ' . $delete . '>Delete</option>';
            echo '<br><br>';
        }


        public function wbw_optional_form_fields_html()
        {
            echo <<<END
    <div class="wbw_info_text">
    <br>Optional form fields, available depending on how your service bodies use BMLT. These can be displayed, displayed and required, or hidden from your end users.
    <br><br>
    </div>
    END;

            $this->do_optional_field('wbw_optional_location_nation', 'Nation');
            $this->do_optional_field('wbw_optional_location_sub_province', 'Sub Province');
        }

        private function do_optional_field($option, $friendlyname)
        {

            $value = $this->WBW_WP_Options->wbw_get_option($option);
            $this->debug_log($value);
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
    </label><select id="${option}" name="${option}">
    <option name="hidden" value="hidden" ${hidden}>Hidden</option>
    <option name="displayrequired" value="displayrequired" ${displayrequired}>Display + Required Field</option>
    <option name="display" value="display" ${display}>Display Only</option>
    </select>
    <br><br>
    END;
        }

        public function wbw_fso_email_address_html()
        {
            $from_address = $this->WBW_WP_Options->wbw_get_option('wbw_fso_email_address');
            echo <<<END
    <div class="wbw_info_text">
    <br>The email address to notify the FSO that starter kits are required.
    <br><br>
    </div>
    END;

            echo '<br><label for="wbw_email_from_address"><b>FSO Email Address:</b></label><input type="text" size="50" id="wbw_fso_email_address" name="wbw_fso_email_address" value="' . $from_address . '"/>';
            echo '<br><br>';
        }

        public function wbw_fso_email_template_html()
        {

            echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing the FSO about starter kit requests.
    <br><br>
    </div>
    END;
            $content = $this->WBW_WP_Options->wbw_get_option('wbw_fso_email_template');
            $editor_id = 'wbw_fso_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
            echo '<br><br>';
        }

        public function wbw_submitter_email_template_html()
        {

            echo <<<END
    <div class="wbw_info_text">
    <br>This template will be used when emailing a submitter about the meeting change they've requested.
    <br><br>
    </div>
    END;
            $content = $this->WBW_WP_Options->wbw_get_option('wbw_submitter_email_template');
            $editor_id = 'wbw_submitter_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . $editor_id . '_default">Copy default template to clipboard</button>';
            echo '<br><br>';
        }


        public function display_wbw_admin_options_page()
        {
            $content = '';
            ob_start();
            include('admin/admin_options.php');
            $content = ob_get_clean();
            echo $content;
        }

        public function display_wbw_admin_submissions_page()
        {
            $content = '';
            ob_start();
            include('admin/admin_submissions.php');
            $content = ob_get_clean();
            echo $content;
        }

        public function display_wbw_admin_service_bodies_page()
        {
            $content = '';
            ob_start();
            include('admin/admin_service_bodies.php');
            $content = ob_get_clean();
            echo $content;
        }

        public function wbw_install()
        {

            // install all our default options (if they arent set already)
            add_option('wbw_email_from_address','example@example');
            add_option('wbw_delete_closed_meetings','unpublish');
            add_option('wbw_optional_location_nation','hidden');
            add_option('wbw_optional_location_sub_province','hidden');
            add_option('wbw_submitter_email_template',file_get_contents(WBW_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
            add_option('wbw_fso_email_template',file_get_contents(WBW_PLUGIN_DIR . 'templates/default_fso_email_template.html'));
            add_option('wbw_fso_email_address','example@example.example');

            $this->WBW_Database->wbw_db_upgrade($this->WBW_Database->wbw_db_version, false);

            // give all 'manage_options" users the capability so they are able to see the submission menu
            $users = get_users();
            foreach ($users as $user) {
                if($user->has_cap('manage_options'))
                {
                    $user->add_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
                }
            }
            // add a custom role just for trusted servants
            add_role('wbw_trusted_servant', 'BMLT Workflow Trusted Servant');
        }

        public function wbw_add_capability( $user_id ) {

            // give all 'manage_options" users the capability on create so they are able to see the submission menu
            $user = get_user_by('id',$user_id);
                if($user->has_cap('manage_options'))
                {
                    $user->add_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
                    $this->debug_log("adding capabilities to new user");
                }           
        }

        public function wbw_uninstall()
        {
            global $wpdb;

            // remove custom capability

            $this->debug_log("deleting capabilities");

            $users = get_users();
            foreach ($users as $user) {
                $user->remove_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
            }

            remove_role('wbw_trusted_servant');
        }
    }

    $start_plugin = new wbw_plugin();
}

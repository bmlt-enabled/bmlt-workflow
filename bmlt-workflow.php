<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin Name: BMLT Workflow
 * Plugin URI: https://github.com/bmlt-enabled/bmlt-workflow
 * Description: Workflows for BMLT meeting management!
 * Version: 1.0.4
 * Requires at least: 5.2
 * Tested up to: 6.0
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/

 
define('BMLTWF_PLUGIN_VERSION', '1.0.4');

if ((!defined('ABSPATH') && (!defined('BMLTWF_RUNNING_UNDER_PHPUNIT')))) exit; // die if being called directly

require 'config.php';

if (file_exists('vendor/autoload.php')) {
    // use composer autoload if we're running under phpunit
    include 'vendor/autoload.php';
} else {
    // custom autoloader if not. only autoloads out of src directory
    spl_autoload_register(function (string $class) {
        if (strpos($class, 'bmltwf\\') === 0) {
            $class = str_replace('bmltwf\\', '', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        }
    });
}

use bmltwf\BMLT\Integration;
use bmltwf\REST\Controller;
use bmltwf\BMLTWF_Database;
use bmltwf\BMLTWF_WP_Options;
use bmltwf\BMLTWF_Rest;

// database configuration
global $wpdb;

if (!class_exists('bmltwf_plugin')) {
    class bmltwf_plugin
    {
        use \bmltwf\BMLTWF_Debug;

        public function __construct()
        {
            $this->BMLTWF_WP_Options = new BMLTWF_WP_Options();
            $this->bmlt_integration = new Integration();
            $this->BMLTWF_Rest = new BMLTWF_Rest();
            $this->BMLTWF_Rest_Controller = new Controller();
            $this->BMLTWF_Database = new BMLTWF_Database();

            // actions, shortcodes, menus and filters
            add_action('wp_enqueue_scripts', array(&$this, 'bmltwf_enqueue_form_deps'));
            add_action('admin_menu', array(&$this, 'bmltwf_menu_pages'));
            add_action('admin_enqueue_scripts', array(&$this, 'bmltwf_admin_scripts'));
            add_action('admin_init',  array(&$this, 'bmltwf_register_setting'));
            add_action('rest_api_init', array(&$this, 'bmltwf_rest_controller'));
            add_shortcode('bmltwf-meeting-update-form', array(&$this, 'bmltwf_meeting_update_form'));
            add_filter('plugin_action_links', array(&$this, 'bmltwf_add_plugin_link'), 10, 2);
            add_action('user_register', array(&$this, 'bmltwf_add_capability'), 10, 1);

            register_activation_hook(__FILE__, array(&$this, 'bmltwf_install'));
        }

        public function bmltwf_meeting_update_form($atts = [], $content = null, $tag = '')
        {

            $bmltwf_bmlt_test_status = get_option('bmltwf_bmlt_test_status', "failure");
            if ($bmltwf_bmlt_test_status != "success") {
                wp_die("<h4>BMLTWF Plugin Error: BMLT Root Server not configured and tested.</h4>");
            }

            // base css and js for this page
            $this->prevent_cache_enqueue_script('bmltwf-meeting-update-form-js', array('jquery'), 'js/meeting_update_form.js');
            $this->prevent_cache_enqueue_style('bmltwf-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->prevent_cache_enqueue_script('bmltwf-general-js', array('jquery'), 'js/script_includes.js');
            wp_enqueue_style('dashicons');

            // jquery validation

            wp_enqueue_script('jqueryvalidate');
            wp_enqueue_script('jqueryvalidateadditional');

            // select2
            $this->enqueue_select2();

            // inline scripts
            $script  = 'var bmltwf_form_submit_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/submissions') . '; ';
            $script .= 'var bmltwf_bmlt_server_address = "' . get_option('bmltwf_bmlt_server_address') . '";';
            // optional fields
            $script .= 'var bmltwf_optional_location_nation = "' . get_option('bmltwf_optional_location_nation') . '";';
            $script .= 'var bmltwf_optional_location_sub_province = "' . get_option('bmltwf_optional_location_sub_province') . '";';
            $script .= 'var bmltwf_optional_postcode = "' . get_option('bmltwf_optional_postcode') . '";';
            $script .= 'var bmltwf_fso_feature = "' . get_option('bmltwf_fso_feature') . '";';

            // add meeting formats
            $formatarr = $this->bmlt_integration->getMeetingFormats();
            // $this->debug_log("FORMATS");
            // $this->debug_log($formatarr);
            // $this->debug_log(json_encode($formatarr));
            $script .= 'var bmltwf_bmlt_formats = ' . json_encode($formatarr) . '; ';

            // do a one off lookup for our servicebodies
            $url = '/' . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/servicebodies';
            $this->debug_log("rest url = " . $url);

            $request  = new WP_REST_Request('GET', $url);
            $response = rest_do_request($request);
            $result = rest_get_server()->response_to_data($response, true);
            if (count($result) == 0) {
                wp_die("<h4>BMLTWF Plugin Error: Service bodies not configured.</h4>");
            }
            $script .= 'var bmltwf_service_bodies = ' . json_encode($result) . '; ';

            $this->debug_log("adding script " . $script);
            $status = wp_add_inline_script('bmltwf-meeting-update-form-js', $script, 'before');
            $this->prevent_cache_enqueue_script('bmltwf-meeting-update-form-js', array('jquery'), 'js/meeting_update_form.js');

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
            wp_register_style('select2css', plugin_dir_url(__FILE__) . '/thirdparty/css/select2@4.1.0-rc.0/dist/css/select2.min.css', false, '1.0', 'all');
            wp_register_script('select2', plugin_dir_url(__FILE__) . '/thirdparty/js/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '1.0', true);
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

        public function bmltwf_enqueue_form_deps()
        {

            $this->register_select2();
            wp_register_script('jqueryvalidate', plugin_dir_url(__FILE__) . '/thirdparty/js/jquery-validation@1.19.3/dist/jquery.validate.min.js', array('jquery'), '1.0', true);
            wp_register_script('jqueryvalidateadditional', plugin_dir_url(__FILE__) . '/thirdparty/js/jquery-validation@1.19.3/dist/additional-methods.min.js', array('jquery', 'jqueryvalidate'), '1.0', true);
            $this->prevent_cache_register_script('bmltwf-general-js', array('jquery'), 'js/script_includes.js');
            $this->prevent_cache_register_script('bmltwf-meeting-update-form-js', array('jquery', 'jqueryvalidate', 'jqueryvalidateadditional'), 'js/meeting_update_form.js');
            $this->prevent_cache_register_style('bmltwf-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->debug_log("scripts and styles registered");
        }

        public function bmltwf_admin_scripts($hook)
        {


            // $this->debug_log($hook);

            if (($hook != 'toplevel_page_bmltwf-settings') && ($hook != 'bmlt-workflow_page_bmltwf-submissions') && ($hook != 'bmlt-workflow_page_bmltwf-service-bodies')) {
                return;
            }

            $this->prevent_cache_enqueue_script('bmltwfjs', array('jquery'), 'js/script_includes.js');

            switch ($hook) {

                case ('toplevel_page_bmltwf-settings'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_style('bmltwf-admin-css', false, 'css/admin_options.css');
                    $this->prevent_cache_enqueue_script('admin_options_js', array('jquery'), 'js/admin_options.js');

                    // clipboard
                    wp_enqueue_script('clipboard');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // inline scripts
                    $script  = 'var bmltwf_admin_bmltserver_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/bmltserver') . '; ';
                    $script .= 'var bmltwf_admin_backup_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/options/backup') . '; ';
                    $script .= 'var bmltwf_admin_restore_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/options/restore') . '; ';
                    $script .= 'var bmltwf_admin_bmltwf_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/servicebodies') . '; ';
                    $script .= 'var bmltwf_fso_feature = "' . get_option('bmltwf_fso_feature') . '";';

                    wp_add_inline_script('admin_options_js', $script, 'before');
                    break;

                case ('bmlt-workflow_page_bmltwf-submissions'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('admin_submissions_js', array('jquery'), 'js/admin_submissions.js');
                    $this->prevent_cache_enqueue_style('bmltwf-admin-submissions-css', false, 'css/admin_submissions.css');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // datatables
                    wp_register_style('dtcss', plugin_dir_url(__FILE__) . '/thirdparty/DataTables/datatables.min.css', false, '1.0', 'all');
                    wp_register_script('dt', plugin_dir_url(__FILE__) . '/thirdparty/DataTables/datatables.min.js', array('jquery'), '1.0', true);
                    wp_enqueue_style('dtcss');
                    wp_enqueue_script('dt');

                    // select2 for quick editor
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest urls are populated
                    $script  = 'var bmltwf_admin_submissions_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/submissions/') . '; ';
                    $script  .= 'var bmltwf_bmltserver_geolocate_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/bmltserver/geolocate') . '; ';
                    // add our bmlt server for the submission lookups
                    $script .= 'var bmltwf_bmlt_server_address = "' . get_option('bmltwf_bmlt_server_address') . '";';

                    // add meeting formats
                    $formatarr = $this->bmlt_integration->getMeetingFormats();
                    $this->debug_log("FORMATS");
                    $this->debug_log(($formatarr));
                    $this->debug_log(json_encode($formatarr));
                    $script .= 'var bmltwf_bmlt_formats = ' . json_encode($formatarr) . '; ';

                    // do a one off lookup for our servicebodies
                    $url = '/' . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/servicebodies';

                    $request  = new WP_REST_Request('GET', $url);
                    $response = rest_do_request($request);
                    $result     = rest_get_server()->response_to_data($response, true);
                    $script .= 'var bmltwf_admin_bmltwf_service_bodies = ' . json_encode($result) . '; ';

                    // defaults for approve close form
                    $bmltwf_default_closed_meetings = get_option('bmltwf_delete_closed_meetings');
                    $script .= 'var bmltwf_default_closed_meetings = "' . $bmltwf_default_closed_meetings . '"; ';

                    // optional fields in quickedit
                    $script .= 'var bmltwf_optional_location_nation = "' . get_option('bmltwf_optional_location_nation') . '";';
                    $script .= 'var bmltwf_optional_location_sub_province = "' . get_option('bmltwf_optional_location_sub_province') . '";';
                    $script .= 'var bmltwf_optional_postcode = "' . get_option('bmltwf_optional_postcode') . '";';

                    wp_add_inline_script('admin_submissions_js', $script, 'before');

                    break;

                case ('bmlt-workflow_page_bmltwf-service-bodies'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('admin_service_bodies_js', array('jquery'), 'js/admin_service_bodies.js');
                    $this->prevent_cache_enqueue_style('bmltwf-admin-submissions-css', false, 'css/admin_service_bodies.css');

                    // select2
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest url is populated
                    $script = 'var bmltwf_admin_bmltwf_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->BMLTWF_Rest->bmltwf_rest_namespace . '/servicebodies') . '; ';
                    $script .= 'var wp_users_url = ' . json_encode(get_rest_url() . 'wp/v2/users') . '; ';
                    wp_add_inline_script('admin_service_bodies_js', $script, 'before');
                    break;
            }
        }

        public function bmltwf_menu_pages()
        {

            add_menu_page(
                'BMLT Workflow',
                'BMLT Workflow',
                'manage_options',
                'bmltwf-settings',
                '',
                'dashicons-analytics',
                null
            );

            add_submenu_page(
                'bmltwf-settings',
                'Configuration',
                'Configuration',
                'manage_options',
                'bmltwf-settings',
                array(&$this, 'display_bmltwf_admin_options_page'),
                2
            );

            add_submenu_page(
                'bmltwf-settings',
                'Workflow Submissions',
                'Workflow Submissions',
                $this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions,
                'bmltwf-submissions',
                array(&$this, 'display_bmltwf_admin_submissions_page'),
                2
            );

            add_submenu_page(
                'bmltwf-settings',
                'Service Bodies',
                'Service Bodies',
                'manage_options',
                'bmltwf-service-bodies',
                array(&$this, 'display_bmltwf_admin_service_bodies_page'),
                2
            );
        }

        public function bmltwf_add_plugin_link($plugin_actions, $plugin_file)
        {

            $new_actions = array();
            if (basename(plugin_dir_path(__FILE__)) . '/bmlt-workflow.php' === $plugin_file) {
                $new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'comment-limiter'), esc_url(admin_url('admin.php?page=bmltwf-settings')));
            }

            return array_merge($new_actions, $plugin_actions);
        }

        public function bmltwf_rest_controller()
        {
            $this->BMLTWF_Rest_Controller->register_routes();
        }

        public function bmltwf_register_setting()
        {

            $this->debug_log("registering settings");

            if (!current_user_can('activate_plugins')) {
                wp_die("This page cannot be accessed");
            }

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_email_from_address',
                array(
                    'type' => 'string',
                    'description' => 'Email from address',
                    'sanitize_callback' => array(&$this, 'bmltwf_email_from_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_delete_closed_meetings',
                array(
                    'type' => 'string',
                    'description' => 'Default for close meeting submission',
                    'sanitize_callback' => array(&$this, 'bmltwf_delete_closed_meetings_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'unpublish'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_nation',
                array(
                    'type' => 'string',
                    'description' => 'optional field for location_nation',
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_location_nation_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'hidden'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_sub_province',
                array(
                    'type' => 'string',
                    'description' => 'optional field for location_sub_province',
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_location_sub_province_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'hidden'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_postcode',
                array(
                    'type' => 'string',
                    'description' => 'optional field for postcode',
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_postcode_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'display'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_submitter_email_template',
                array(
                    'type' => 'string',
                    'description' => 'bmltwf_submitter_email_template',
                    'sanitize_callback' => null,
                    'show_in_rest' => false,
                    'default' => file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_submitter_email_template.html')
                )
            );
            register_setting(
                'bmltwf-settings-group',
                'bmltwf_fso_feature',
                array(
                    'type' => 'string',
                    'description' => 'bmltwf_fso_feature',
                    'sanitize_callback' => array(&$this, 'bmltwf_fso_feature_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'display'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_fso_email_template',
                array(
                    'type' => 'string',
                    'description' => 'bmltwf_fso_email_template',
                    'sanitize_callback' => null,
                    'show_in_rest' => false,
                    'default' => file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_fso_email_template.html')
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_fso_email_address',
                array(
                    'type' => 'string',
                    'description' => 'FSO email address',
                    'sanitize_callback' => array(&$this, 'bmltwf_fso_email_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example.example'
                )
            );

            add_settings_section(
                'bmltwf-settings-section-id',
                '',
                '',
                'bmltwf-settings'
            );

            add_settings_field(
                'bmltwf_bmlt_server_address',
                'BMLT Root Server Configuration',
                array(&$this, 'bmltwf_bmlt_server_address_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_backup_restore',
                'Backup and Restore',
                array(&$this, 'bmltwf_backup_restore_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_shortcode',
                'Meeting Update Form Shortcode',
                array(&$this, 'bmltwf_shortcode_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_email_from_address',
                'Email From Address',
                array(&$this, 'bmltwf_email_from_address_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_delete_closed_meetings',
                'Default for close meeting submission',
                array(&$this, 'bmltwf_delete_closed_meetings_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_optional_form_fields',
                'Optional form fields',
                array(&$this, 'bmltwf_optional_form_fields_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            add_settings_field(
                'bmltwf_fso_options',
                'Field Service Office configuration',
                array(&$this, 'bmltwf_fso_options_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );

            // add_settings_field(
            //     'bmltwf_fso_email_address',
            //     'Email address for the FSO (Starter Kit Notifications)',
            //     array(&$this, 'bmltwf_fso_email_address_html'),
            //     'bmltwf-settings',
            //     'bmltwf-settings-section-id'
            // );


            // add_settings_field(
            //     'bmltwf_fso_email_template',
            //     'Email Template for FSO emails (Starter Kit Notifications)',
            //     array(&$this, 'bmltwf_fso_email_template_html'),
            //     'bmltwf-settings',
            //     'bmltwf-settings-section-id'
            // );

            add_settings_field(
                'bmltwf_submitter_email_template',
                'Email Template for New Meeting',
                array(&$this, 'bmltwf_submitter_email_template_html'),
                'bmltwf-settings',
                'bmltwf-settings-section-id'
            );
        }
        public function bmltwf_fso_feature_sanitize_callback($input)
        {
            $this->debug_log("fso_enablde sanitize callback");
            $this->debug_log($input);

            $output = get_option('bmltwf_fso_feature');
            switch ($input) {
                case 'hidden':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_fso_feature', 'err', 'Invalid FSO Enabled setting.');
            return $output;
        }

        public function bmltwf_optional_postcode_sanitize_callback($input)
        {
            $this->debug_log("postcode sanitize callback");
            $this->debug_log($input);

            $output = get_option('bmltwf_optional_postcode');
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_postcode', 'err', 'Invalid Postcode setting.');
            return $output;
        }

        public function bmltwf_optional_location_nation_sanitize_callback($input)
        {
            $this->debug_log("location nation sanitize callback");
            $this->debug_log($input);

            $output = get_option('bmltwf_optional_location_nation');
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_location_nation', 'err', 'Invalid Nation setting.');
            return $output;
        }

        public function bmltwf_optional_location_sub_province_sanitize_callback($input)
        {
            $output = get_option('bmltwf_optional_location_sub_province');
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_location_sub_province', 'err', 'Invalid Sub Province setting.');
            return $output;
        }

        public function bmltwf_email_from_address_sanitize_callback($input)
        {
            $output = get_option('bmltwf_email_from_address');
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email)) {
                add_settings_error('bmltwf_email_from_address', 'err', 'Invalid email from address.');
                return $output;
            }
            return $sanitized_email;
        }

        public function bmltwf_fso_email_address_sanitize_callback($input)
        {
            $output = get_option('bmltwf_fso_email_address');
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email)) {
                add_settings_error('bmltwf_fso_email_address', 'err', 'Invalid FSO email address.');
                return $output;
            }
            return $sanitized_email;
        }

        public function bmltwf_delete_closed_meetings_sanitize_callback($input)
        {
            $output = get_option('bmltwf_delete_closed_meetings');

            switch ($input) {
                case 'delete':
                case 'unpublish':
                    return $input;
            }
            add_settings_error('bmltwf_delete_closed_meetings', 'err', 'Invalid delete closed meetings  setting.');
            return $output;
        }

        public function bmltwf_bmlt_server_address_html()
        {
            echo '<div id="bmltwf_bmlt_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>Your BMLT Root Server details are successfully configured.</div>';
            echo '<div id="bmltwf_bmlt_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>Your BMLT Root Server details are not configured correctly.</div>';
            echo '<div id="bmltwf_servicebodies_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>Your service bodies are successfully configured.</div>';
            echo '<div id="bmltwf_servicebodies_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>Your service bodies are not configured and saved correctly. <a href="?bmltwf-submissions">Fix</a></div>';
            echo '<br>';
            echo '<button type="button" id="bmltwf_configure_bmlt_server">Update BMLT Root Server Configuration</button>';
            echo '<br>';
        }

        public function bmltwf_backup_restore_html()
        {
            echo '<button type="button" id="bmltwf_backup">Backup Configuration</button>   <button type="button" id="bmltwf_restore">Restore Configuration</button><input type="file" id="bmltwf_file_selector" accept=".json,application/json" style="display:none">';
            echo '<span class="spinner" id="bmltwf-backup-spinner"></span><br>';
        }

        public function bmltwf_shortcode_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>You can use the shortcode <code>[bmltwf-meeting-update-form]</code> to list the appropriate meetings and service areas in your update form.';
            echo '<br><br>';
            echo '</div>';
        }

        public function bmltwf_email_from_address_html()
        {

            $from_address = get_option('bmltwf_email_from_address');
            echo '<div class="bmltwf_info_text">';
            echo '<br>The sender (From:) address of meeting update notification emails. Can contain a display name and email in the form <code>Display Name &lt;example@example.com&gt;</code> or just a standard email address.';
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_email_from_address"><b>From Address:</b></label><input id="bmltwf_email_from_address" type="text" size="50" name="bmltwf_email_from_address" value="' . esc_attr($from_address) . '"/>';
            echo '<br><br>';
        }

        public function bmltwf_delete_closed_meetings_html()
        {

            $selection = get_option('bmltwf_delete_closed_meetings');
            $delete = '';
            $unpublish = '';
            if ($selection === 'delete') {
                $delete = 'selected';
            } else {
                $unpublish = 'selected';
            }

            echo '<div class="bmltwf_info_text">';
            echo '<br>Trusted servants approving a "Close Meeting" request can choose to either Delete or Unpublish. This option selects the default for all trusted servants.';
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_delete_closed_meetings"><b>Close meeting default:</b></label><select id="bmltwf_delete_closed_meetings" name="bmltwf_delete_closed_meetings"><option name="unpublish" value="unpublish" ' . $unpublish . '>Unpublish</option><option name="delete" value="delete" ' . $delete . '>Delete</option>';
            echo '<br><br>';
        }


        public function bmltwf_optional_form_fields_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>Optional form fields, available depending on how your service bodies use BMLT. These can be displayed, displayed and required, or hidden from your end users.';
            echo '<br><br>';
            echo '</div>';

            $this->do_optional_field('bmltwf_optional_location_nation', 'Nation');
            $this->do_optional_field('bmltwf_optional_location_sub_province', 'Sub Province');
            $this->do_optional_field('bmltwf_optional_postcode', 'Post Code');
        }

        private function do_optional_field($option, $friendlyname)
        {

            $value = get_option($option);
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

            echo '<br><label for="' . esc_attr($option) . '"><b>' . esc_attr($friendlyname) . ':</b>';
            echo '</label><select id="' . esc_attr($option) . '" name="' . esc_attr($option) . '">';
            echo '<option name="hidden" value="hidden" ' . esc_attr($hidden) . '>Hidden</option>';
            echo '<option name="displayrequired" value="displayrequired" ' . esc_attr($displayrequired) . '>Display + Required Field</option>';
            echo '<option name="display" value="display" ' . esc_attr($display) . '>Display Only</option>';
            echo '</select>';
            echo '<br><br>';
        }

        public function bmltwf_fso_options_html()
        {
            $hidden = '';
            $display = '';

            echo '<div class="bmltwf_info_text">';
            echo '<br>Enable this setting to display the starter kit option in the submission form and to configure the email address for your Field Service Office.';
            echo '<br><br>';
            echo '</div>';

            $fso_enabled = get_option('bmltwf_fso_feature');
            $from_address = get_option('bmltwf_fso_email_address');

            switch ($fso_enabled) {
                case 'hidden':
                    $hidden = 'selected';
                    break;
                case 'display':
                    $display = 'selected';
                    break;
            }

            echo '<br><label for="bmltwf_fso_feature"><b>FSO Features:</b>';
            echo '</label><select id="bmltwf_fso_feature" name="bmltwf_fso_feature">';
            echo '<option name="hidden" value="hidden" ' . esc_attr($hidden) . '>Disabled</option>';
            echo '<option name="display" value="display" ' . esc_attr($display) . '>Enabled</option>';
            echo '</select>';
            echo '<br><br>';
            echo '<div id="fso_options">';
            echo '<div class="bmltwf_info_text">';
            echo '<br>The email address to notify the FSO that starter kits are required.';
            echo '<br><br>';
            echo '</div>';


            echo '<br><label for="bmltwf_email_from_address"><b>FSO Email Address:</b></label><input type="text" size="50" id="bmltwf_fso_email_address" name="bmltwf_fso_email_address" value="' . esc_attr($from_address) . '"/>';
            echo '<br><br>';

            echo '<div class="bmltwf_info_text">';
            echo '<br>This template will be used when emailing the FSO about starter kit requests.';
            echo '<br><br>';
            echo '</div>';

            $content = get_option('bmltwf_fso_email_template');
            $editor_id = 'bmltwf_fso_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">Copy default template to clipboard</button>';
            echo '<br><br>';
            echo '</div>';
        }

        public function bmltwf_submitter_email_template_html()
        {

            echo '<div class="bmltwf_info_text">';
            echo "<br>This template will be used when emailing a submitter about the meeting change they've requested.";
            echo '<br><br>';
            echo '</div>';

            $content = get_option('bmltwf_submitter_email_template');
            $editor_id = 'bmltwf_submitter_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">Copy default template to clipboard</button>';
            echo '<br><br>';
        }


        public function display_bmltwf_admin_options_page()
        {
            include_once('admin/admin_options.php');
        }

        public function display_bmltwf_admin_submissions_page()
        {
            include_once('admin/admin_submissions.php');
        }

        public function display_bmltwf_admin_service_bodies_page()
        {
            include_once('admin/admin_service_bodies.php');
        }

        public function bmltwf_install($networkwide)
        {
            global $wpdb;
            $this->debug_log("is_multisite = " . var_export(is_multisite(),true));
            $this->debug_log("is_plugin_active_for_network = " . var_export(is_plugin_active_for_network(__FILE__),true));
            $this->debug_log("networkwide = " . var_export($networkwide,true));
            if ((is_multisite()) && ($networkwide === true)) {
                // multi site and network activation, so iterate through all blogs
                $this->debug_log('Multisite Network Activation');
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    $this->debug_log('Installing on blog id ' . $blog_id);
                    switch_to_blog($blog_id);
                    $this->bmltwf_add_default_options();
                    $blogdb = new BMLTWF_Database();
                    $blogdb->bmltwf_db_upgrade($blogdb->bmltwf_db_version, false);
                    restore_current_blog();
                }
            } else {
                $this->debug_log('Single Site Activation');
                $this->bmltwf_add_default_options();
                $this->BMLTWF_Database->bmltwf_db_upgrade($this->BMLTWF_Database->bmltwf_db_version, false);
            }

            // give all 'manage_options" users the capability so they are able to see the submission menu
            $users = get_users();
            foreach ($users as $user) {
                $this->bmltwf_add_capability_to_manage_options_user($user);
            }

            // add a custom role just for trusted servants
            add_role('bmltwf_trusted_servant', 'BMLT Workflow Trusted Servant');
        }

        private function bmltwf_add_default_options()
        {
            // install all our default options (if they arent set already)
            add_option('bmltwf_email_from_address', 'example@example');
            add_option('bmltwf_delete_closed_meetings', 'unpublish');
            add_option('bmltwf_optional_location_nation', 'hidden');
            add_option('bmltwf_optional_location_sub_province', 'hidden');
            add_option('bmltwf_optional_postcode', 'display');
            add_option('bmltwf_submitter_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
            add_option('bmltwf_fso_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_fso_email_template.html'));
            add_option('bmltwf_fso_email_address', 'example@example.example');
            add_option('bmltwf_fso_feature', 'display');
        }

        public function bmltwf_add_capability($user_id)
        {
            // give all 'manage_options" users the capability on create so they are able to see the submission menu
            $this->bmltwf_add_capability_to_manage_options_user(get_user_by('id', $user_id));
        }

        private function bmltwf_add_capability_to_manage_options_user($user)
        {
            if ($user->has_cap('manage_options')) {
                $user->add_cap($this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
                $this->debug_log("adding capabilities to user " . $user->get('ID'));
            }
        }
    }

    $start_plugin = new bmltwf_plugin();
}

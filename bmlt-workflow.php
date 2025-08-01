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
 * Version: 1.1.30
 * Requires at least: 5.2
 * Tested up to: 6.8.2
 * Author: @nigel-bmlt
 * Author URI: https://github.com/nigel-bmlt
 **/


define('BMLTWF_PLUGIN_VERSION', '1.1.30');

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
use bmltwf\BMLTWF_Rest;

// database configuration
global $wpdb;

if (!class_exists('bmltwf_plugin')) {
    class bmltwf_plugin
    {
        use \bmltwf\BMLTWF_Debug;
        use \bmltwf\BMLTWF_Constants;

        private Integration $bmlt_integration;
        private Controller $BMLTWF_Rest_Controller;
        private BMLTWF_Database $BMLTWF_Database;

        public function __construct()
        {
            // Set global debug flag to avoid repeated database calls
            global $bmltwf_debug_enabled;
            $bmltwf_debug_enabled = (get_option('bmltwf_enable_debug', 'false') === 'true');
            
            // Initialize debug file if debug is enabled
            if ($bmltwf_debug_enabled) {
                $this->debug_log("BMLT Workflow plugin initialized");
            }
            
            // $this->debug_log("bmlt-workflow: Creating new Integration");
            $this->bmlt_integration = new Integration();
            // $this->debug_log("bmlt-workflow: Creating new Controller");
            $this->BMLTWF_Rest_Controller = new Controller();
            // $this->debug_log("bmlt-workflow: Creating new BMLTWF_Database");
            $this->BMLTWF_Database = new BMLTWF_Database();
            
            // Check database version and upgrade if needed
            $this->check_database_version();

            // ensure our default options are always available
            $this->bmltwf_add_default_options();

            // actions, shortcodes, menus and filters
            add_action('wp_enqueue_scripts', array(&$this, 'bmltwf_enqueue_form_deps'));
            add_action('admin_menu', array(&$this, 'bmltwf_menu_pages'));
            add_action('admin_enqueue_scripts', array(&$this, 'bmltwf_admin_scripts'));
            add_action('admin_init',  array(&$this, 'bmltwf_register_setting'));
            add_action('rest_api_init', array(&$this, 'bmltwf_rest_controller'));
            add_shortcode('bmltwf-meeting-update-form', array(&$this, 'bmltwf_meeting_update_form'));
            add_shortcode('bmltwf-correspondence-form', array(&$this, 'bmltwf_correspondence_form'));
            add_filter('plugin_action_links', array(&$this, 'bmltwf_add_plugin_link'), 10, 2);
            add_action('user_register', array(&$this, 'bmltwf_add_capability'), 10, 1);
            register_activation_hook(__FILE__, array(&$this, 'bmltwf_install'));
        }

        /**
         * Check database version and upgrade if needed
         */
        private function check_database_version()
        {
            $installed_version = get_option('bmltwf_db_version');
            $current_version = $this->BMLTWF_Database->bmltwf_db_version;
            
            if ($installed_version === false || version_compare($installed_version, $current_version, '<')) {
                $this->debug_log("Database upgrade needed from {$installed_version} to {$current_version}");
                $result = $this->BMLTWF_Database->bmltwf_db_upgrade($current_version, false);
                if ($result === 2) {
                    $this->debug_log("Database upgraded successfully");
                } elseif ($result === 1) {
                    $this->debug_log("Database fresh install completed");
                } else {
                    $this->debug_log("No database changes needed");
                }
            }
        }
        
        public function bmltwf_load_textdomain()
        {
            $domain = 'bmlt-workflow';
            $locale = get_locale();
            $mofile = dirname(plugin_basename(__FILE__)) . '/lang/' . $domain . '-' . $locale . '.mo';
            $path = plugin_dir_path(__FILE__) . 'lang/' . $domain . '-' . $locale . '.mo';
            
            // Debug output
            error_log("BMLTWF Debug - Locale: " . $locale);
            error_log("BMLTWF Debug - MO file path: " . $path);
            error_log("BMLTWF Debug - MO file exists: " . (file_exists($path) ? 'YES' : 'NO'));
            
            $result = load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/lang');
            error_log("BMLTWF Debug - load_plugin_textdomain result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            // Test translation
            $test = __('New Meeting', $domain);
            error_log("BMLTWF Debug - Translation test: " . $test);
        }

        public function bmltwf_correspondence_form($atts = [], $content = null, $tag = '')
        {
            // Enqueue required scripts
            wp_enqueue_script('wp-i18n');
            $this->prevent_cache_enqueue_script('bmltwf-correspondence-form-js', array('jquery', 'wp-i18n'), 'js/correspondence_form.js');
            $this->prevent_cache_enqueue_style('bmltwf-correspondence-form-css', false, 'css/correspondence_form.css');
            
            // Set up translations
            wp_set_script_translations('bmltwf-correspondence-form-js', 'bmlt-workflow', plugin_dir_path(__FILE__) . 'lang/');
            
            // Get thread ID from URL parameter
            $thread_id = isset($_GET['thread']) ? sanitize_text_field($_GET['thread']) : '';
            
            // Localize script with REST API URL and thread ID
            wp_localize_script('bmltwf-correspondence-form-js', 'bmltwf_correspondence_data', array(
                'rest_url' => esc_url_raw(rest_url($this->bmltwf_rest_namespace . '/correspondence/thread/' . $thread_id)),
                'thread_id' => $thread_id,
                'nonce' => wp_create_nonce('wp_rest'),
                'submission_rest_url' => esc_url_raw(rest_url($this->bmltwf_rest_namespace . '/submissions/')),
                'i18n' => array(
                    'loading' => __('Loading correspondence...', 'bmlt-workflow'),
                    'error' => __('Error loading correspondence. Please check the URL and try again.', 'bmlt-workflow'),
                    'reply' => __('Reply', 'bmlt-workflow'),
                    'send' => __('Send', 'bmlt-workflow'),
                    'cancel' => __('Cancel', 'bmlt-workflow'),
                    'your_reply' => __('Your reply...', 'bmlt-workflow'),
                    'reply_sent' => __('Your reply has been sent.', 'bmlt-workflow'),
                    'reply_error' => __('Error sending reply. Please try again.', 'bmlt-workflow'),
                )
            ));
            
            include_once('public/correspondence_form.php');
            return bmltwf_correspondence_form_shortcode($atts);
        }

        public function bmltwf_meeting_update_form($atts = [], $content = null, $tag = '')
        {

            $bmltwf_bmlt_test_status = get_option('bmltwf_bmlt_test_status', "failure");
            if ($bmltwf_bmlt_test_status != "success") {
                wp_die("<h4>BMLTWF Plugin Error: BMLT Root Server not configured and tested.</h4>");
            }
            // $this->debug_log(("inside shortcode setup"));
            // base css and js for this page
            $this->prevent_cache_enqueue_script('bmltwf-meeting-update-form-js', array('jquery', 'wp-i18n'), 'js/meeting_update_form.js');
            $this->prevent_cache_enqueue_style('bmltwf-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->prevent_cache_enqueue_script('bmltwf-general-js', array('jquery', 'wp-i18n'), 'js/script_includes.js');
            wp_enqueue_style('dashicons');

            // jquery validation

            wp_enqueue_script('jqueryvalidate');
            wp_enqueue_script('jqueryvalidateadditional');

            // select2
            $this->enqueue_select2();

            // inline scripts
            $script  = 'var bmltwf_form_submit_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/submissions') . '; ';
            $script .= 'var bmltwf_bmltserver_meetings_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/bmltserver/meetings') . '; ';

            // optional fields
            $script .= 'var bmltwf_optional_location_nation = "' . get_option('bmltwf_optional_location_nation') . '";';
            $script .= 'var bmltwf_optional_location_sub_province = "' . get_option('bmltwf_optional_location_sub_province') . '";';
            $script .= 'var bmltwf_optional_location_province = "' . get_option('bmltwf_optional_location_province') . '";';
            $script .= 'var bmltwf_optional_postcode = "' . get_option('bmltwf_optional_postcode') . '";';
            $script .= 'var bmltwf_fso_feature = "' . get_option('bmltwf_fso_feature') . '";';

            // add counties/states/provinces if they are populated
            $meeting_counties_and_sub_provinces = $this->bmlt_integration->getMeetingCounties();
            if (is_wp_error($meeting_counties_and_sub_provinces)) {
                wp_die("<h4>" . __('BMLTWF Plugin Error: Unable to retrieve meeting counties from BMLT server.', 'bmlt-workflow') . "</h4>");
            }
            $script .= "var bmltwf_counties_and_sub_provinces = " . json_encode($meeting_counties_and_sub_provinces) . ";";
            if ($meeting_counties_and_sub_provinces) {
                $script .= json_encode($meeting_counties_and_sub_provinces) . ";";
            } else {
                $script .= "false;";
            }

            $meeting_states_and_provinces = $this->bmlt_integration->getMeetingStates();
            if (is_wp_error($meeting_states_and_provinces)) {
                wp_die("<h4>" . __('BMLTWF Plugin Error: Unable to retrieve meeting states/provinces from BMLT server.', 'bmlt-workflow') . "</h4>");
            }
            $script .= "var bmltwf_do_states_and_provinces = " . json_encode($meeting_states_and_provinces) . ";";
            if ($meeting_states_and_provinces) {
                $script .=  json_encode($meeting_states_and_provinces) . ";";
            } else {
                $script .= "false;";
            }

            $formatarr = $this->bmlt_integration->getMeetingFormats();
            if (is_wp_error($formatarr)) {
                wp_die("<h4>" . __('BMLTWF Plugin Error: Unable to retrieve meeting formats from BMLT server.', 'bmlt-workflow') . "</h4>");
            }

            // $this->debug_log("FORMATS");
            // $this->debug_log($formatarr);
            // $this->debug_log(json_encode($formatarr));
            $script .= 'var bmltwf_bmlt_formats = ' . json_encode($formatarr) . '; ';

            // do a one off lookup for our servicebodies
            $url = '/' . $this->bmltwf_rest_namespace . '/servicebodies';
            // $this->debug_log("rest url = " . $url);

            $request  = new WP_REST_Request('GET', $url);
            $response = rest_do_request($request);
            $result = rest_get_server()->response_to_data($response, true);
            if (count($result) == 0) {
                wp_die("<h4>BMLT Workflow Plugin Error: Service bodies not configured.</h4>");
            }
            $script .= 'var bmltwf_service_bodies = ' . json_encode($result) . '; ';

            $status = wp_add_inline_script('bmltwf-meeting-update-form-js', $script, 'before');

            $a = wp_set_script_translations('bmltwf-meeting-update-form-js', 'bmlt-workflow', plugin_dir_path(__FILE__) . 'lang/');

            if ($a === true) {
                $this->debug_log("script translation succeeded");
            } else {
                $this->debug_log("script translation failed");
            }
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
            $this->prevent_cache_register_script('bmltwf-general-js', array('jquery', 'wp-i18n'), 'js/script_includes.js');
            $this->prevent_cache_register_script('bmltwf-meeting-update-form-js', array('jquery', 'jqueryvalidate', 'jqueryvalidateadditional', 'wp-i18n'), 'js/meeting_update_form.js');
            $this->prevent_cache_register_style('bmltwf-meeting-update-form-css', false, 'css/meeting_update_form.css');
            $this->debug_log("scripts and styles registered");
        }

        public function bmltwf_admin_scripts($hook)
        {
            $this->debug_log("admin scripts");

            $this->debug_log($hook);

            if (($hook != 'toplevel_page_bmltwf-settings') && ($hook != 'bmlt-workflow_page_bmltwf-options') && ($hook != 'toplevel_page_bmltwf-submissions') && ($hook != 'bmlt-workflow_page_bmltwf-submissions') && ($hook != 'bmlt-workflow_page_bmltwf-service-bodies')) {
                return;
            }

            $this->prevent_cache_enqueue_script('bmltwfjs', array('jquery', 'wp-i18n'), 'js/script_includes.js');

            switch ($hook) {

                case ('toplevel_page_bmltwf-settings'):
                case ('bmlt-workflow_page_bmltwf-options'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_style('bmltwf-admin-css', false, 'css/admin_options.css');
                    $this->prevent_cache_enqueue_script('bmltwf-admin-options-js', array('jquery', 'wp-i18n'), 'js/admin_options.js');

                    // clipboard
                    wp_enqueue_script('clipboard');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // inline scripts
                    $script  = 'var bmltwf_admin_bmltserver_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/bmltserver') . '; ';
                    $script .= 'var bmltwf_admin_backup_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/options/backup') . '; ';
                    $script .= 'var bmltwf_admin_restore_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/options/restore') . '; ';
                    $script .= 'var bmltwf_admin_debuglog_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/options/debug') . '; ';
                    $script .= 'var bmltwf_admin_correspondence_page_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/options/correspondence-page') . '; ';
                    $script .= 'var bmltwf_admin_bmltwf_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/servicebodies') . '; ';
                    $script .= 'var bmltwf_fso_feature = "' . get_option('bmltwf_fso_feature') . '";';

                    $script .= 'var bmltwf_google_maps_key_select = ';
                    $google_maps_key = get_option('bmltwf_google_maps_key', '');
                    if ($google_maps_key === '') {
                        $script .= '"bmlt_key";';
                    } else {
                        $script .= '"your_own_key";';
                    }

                    wp_add_inline_script('bmltwf-admin-options-js', $script, 'before');
                    wp_set_script_translations('bmltwf-admin-options-js', 'bmlt-workflow', plugin_dir_path(__FILE__) . 'lang/');

                    break;

                case ('bmlt-workflow_page_bmltwf-submissions'):
                case ('toplevel_page_bmltwf-submissions'):

                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('bmltwf-admin-submissions-js', array('jquery', 'wp-i18n'), 'js/admin_submissions.js');
                    $this->prevent_cache_enqueue_style('bmltwf-admin-submissions-css', false, 'css/admin_submissions.css');

                    // jquery dialog
                    $this->enqueue_jquery_dialog();

                    // datatables
                    wp_register_style('dtcss', plugin_dir_url(__FILE__) . 'thirdparty/DataTables/datatables.min.css', false, '1.0', 'all');
                    wp_register_script('dt', plugin_dir_url(__FILE__) . 'thirdparty/DataTables/datatables.min.js', array('jquery'), '1.0', true);
                    wp_enqueue_style('dtcss');
                    wp_enqueue_script('dt');

                    // select2 for quick editor
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest urls are populated
                    $script  = 'var bmltwf_admin_submissions_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/submissions/') . '; ';
                    $script  .= 'var bmltwf_bmltserver_geolocate_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/bmltserver/geolocate') . '; ';
                    // add our bmlt server for the submission lookups
                    $script .= 'var bmltwf_bmlt_server_address = "' . get_option('bmltwf_bmlt_server_address') . '";';
                    $script .= 'var bmltwf_remove_virtual_meeting_details_on_venue_change = "' . get_option('bmltwf_remove_virtual_meeting_details_on_venue_change') . '";';
                    $script .= 'var bmltwf_optional_location_sub_province_displayname = "' . sanitize_text_field(get_option('bmltwf_optional_location_sub_province_displayname')) . '";';
                    $script .= 'var bmltwf_optional_location_province_displayname = "' . sanitize_text_field(get_option('bmltwf_optional_location_province_displayname')) . '";';
                    $script .= 'var bmltwf_optional_location_postal_code_1_displayname = "' . sanitize_text_field(get_option('bmltwf_optional_postcode_displayname')) . '";';
                    $script .= 'var bmltwf_optional_location_nation_displayname = "' . sanitize_text_field(get_option('bmltwf_optional_location_nation_displayname')) . '";';

                    // add counties/states/provinces if they are populated
                    $meeting_counties_and_sub_provinces = $this->bmlt_integration->getMeetingCounties();
                    $script .= "var bmltwf_counties_and_sub_provinces = " . json_encode($meeting_counties_and_sub_provinces) . ";";

                    $meeting_states_and_provinces = $this->bmlt_integration->getMeetingStates();
                    $script .= "var bmltwf_do_states_and_provinces = " . json_encode($meeting_states_and_provinces) . ";";

                    // handling for zip and county auto geocoding
                    $script .= "var bmltwf_zip_auto_geocoding = " . json_encode($this->bmlt_integration->isAutoGeocodingEnabled('zip')) . ";";
                    $script .= "var bmltwf_county_auto_geocoding = " . json_encode($this->bmlt_integration->isAutoGeocodingEnabled('county')) . ";";

                    // add meeting formats
                    $formatarr = $this->bmlt_integration->getMeetingFormats();

                    $script .= 'var bmltwf_bmlt_formats = ' . json_encode($formatarr) . '; ';

                    // do a one off lookup for our servicebodies
                    $url = '/' . $this->bmltwf_rest_namespace . '/servicebodies';

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
                    $script .= 'var bmltwf_optional_location_province = "' . get_option('bmltwf_optional_location_province') . '";';
                    $script .= 'var bmltwf_optional_postcode = "' . get_option('bmltwf_optional_postcode') . '";';
                    $key = $this->bmlt_integration->getGmapsKey();
                    if (\is_wp_error($key)) {
                        $key = "";
                    } else {
                        $script .= 'var bmltwf_gmaps_key = "' . $key . '";';
                    }
                    
                    $script .= 'var bmltwf_auto_geocoding_enabled = ' . json_encode($this->bmlt_integration->isAutoGeocodingEnabled('auto')) . ';';

                    // can current user use the delete button?
                    $show_delete = "false";
                    if (get_option('bmltwf_trusted_servants_can_delete_submissions') == 'true') {
                        $show_delete = "true";
                    } else if (current_user_can('manage_options')) {
                        $show_delete = "true";
                    }
                    $script .= 'var bmltwf_datatables_delete_enabled = ' . $show_delete . ';';
                    
                    // correspondence page configuration
                    $correspondence_page_id = get_option('bmltwf_correspondence_page');
                    $correspondence_enabled = 'false';
                    if (!empty($correspondence_page_id)) {
                        $page = get_post($correspondence_page_id);
                        if ($page && $page->post_status === 'publish' && has_shortcode($page->post_content, 'bmltwf-correspondence-form')) {
                            $correspondence_enabled = 'true';
                        }
                    }
                    $script .= 'var bmltwf_correspondence_enabled = ' . $correspondence_enabled . ';';

                    wp_add_inline_script('bmltwf-admin-submissions-js', $script, 'before');
                    wp_set_script_translations('bmltwf-admin-submissions', 'bmlt-workflow', plugin_dir_path(__FILE__) . 'lang/');

                    break;

                case ('bmlt-workflow_page_bmltwf-service-bodies'):
                    // base css and scripts for this page
                    $this->prevent_cache_enqueue_script('bmltwf-admin-service-bodies-js', array('jquery', 'wp-i18n'), 'js/admin_service_bodies.js');
                    $this->prevent_cache_enqueue_style('bmltwf-admin-submissions-css', false, 'css/admin_service_bodies.css');

                    // select2
                    $this->register_select2();
                    $this->enqueue_select2();

                    // make sure our rest url is populated
                    $script = 'var bmltwf_admin_bmltwf_service_bodies_rest_url = ' . json_encode(get_rest_url() . $this->bmltwf_rest_namespace . '/servicebodies') . '; ';
                    $script .= 'var wp_users_url = ' . json_encode(get_rest_url() . 'wp/v2/users') . '; ';
                    wp_add_inline_script('bmltwf-admin-service-bodies-js', $script, 'before');
                    wp_set_script_translations('bmltwf-admin-service-bodies-js', 'bmlt-workflow', plugin_dir_path(__FILE__) . 'lang/');
                    break;
            }
        }

        public function bmltwf_menu_pages()
        {
            $toplevelslug = 'bmltwf-settings';

            // if we're just a submission editor, make our submissions page the landing page
            if (!current_user_can('manage_options') && (current_user_can($this->bmltwf_capability_manage_submissions))) {
                $toplevelslug = 'bmltwf-submissions';
            }

            // give our admins the view capability on the fly
            if (current_user_can('manage_options') && (!current_user_can($this->bmltwf_capability_manage_submissions))) {
                global $current_user;
                $current_user->add_cap($this->bmltwf_capability_manage_submissions);
            }

            // $this->debug_log("slug = ".$toplevelslug);
            add_menu_page(
                'BMLT Workflow',
                'BMLT Workflow',
                $this->bmltwf_capability_manage_submissions,
                $toplevelslug,
                '',
                'dashicons-analytics',
                null
            );

            add_submenu_page(
                'bmltwf-settings',
                __('Configuration', 'bmlt-workflow'),
                __('Configuration', 'bmlt-workflow'),
                'manage_options',
                'bmltwf-options',
                array(&$this, 'display_bmltwf_admin_options_page'),
                2
            );

            add_submenu_page(
                'bmltwf-settings',
                __('Workflow Submissions', 'bmlt-workflow'),
                __('Workflow Submissions', 'bmlt-workflow'),
                $this->bmltwf_capability_manage_submissions,
                'bmltwf-submissions',
                array(&$this, 'display_bmltwf_admin_submissions_page'),
                2
            );

            add_submenu_page(
                'bmltwf-settings',
                __('Service Bodies', 'bmlt-workflow'),
                __('Service Bodies', 'bmlt-workflow'),
                'manage_options',
                'bmltwf-service-bodies',
                array(&$this, 'display_bmltwf_admin_service_bodies_page'),
                2
            );
            if (!current_user_can('manage_options') && (current_user_can($this->bmltwf_capability_manage_submissions))) {
                remove_menu_page('bmltwf-settings');
            }
            
            // Remove the duplicate submenu item that WordPress automatically creates
            remove_submenu_page('bmltwf-settings', 'bmltwf-settings');
        }

        public function bmltwf_add_plugin_link($plugin_actions, $plugin_file)
        {

            $new_actions = array();
            if (basename(plugin_dir_path(__FILE__)) . '/bmlt-workflow.php' === $plugin_file) {
                $new_actions['cl_settings'] = sprintf('<a href="%s">Settings</a>', esc_url(admin_url('admin.php?page=bmltwf-settings')));
            }

            return array_merge($new_actions, $plugin_actions);
        }

        public function bmltwf_rest_controller()
        {
            $this->BMLTWF_Rest_Controller->register_routes();
        }

        public function bmltwf_register_setting()
        {

            if (!current_user_can('activate_plugins')) {
                return;
            }

            $this->debug_log("registering settings");

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_email_from_address',
                array(
                    'type' => 'string',
                    'description' => __('Email from address', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_email_from_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example.com'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_google_maps_key',
                array(
                    'type' => 'string',
                    'description' => __('Google maps key', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_google_maps_key_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => ''
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_delete_closed_meetings',
                array(
                    'type' => 'string',
                    'description' => __('Default behaviour when closing meetings', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_delete_closed_meetings_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'unpublish'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_trusted_servants_can_delete_submissions',
                array(
                    'type' => 'string',
                    'description' => __('Trusted servants can delete submissions', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_trusted_servants_can_delete_submissions_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'false'
                )
            );


            register_setting(
                'bmltwf-settings-group',
                'bmltwf_remove_virtual_meeting_details_on_venue_change',
                array(
                    'type' => 'string',
                    'description' => __('Remove virtual meeting details on venue change', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_remove_virtual_meeting_details_on_venue_change_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'false'
                )
            );
            
            register_setting(
                'bmltwf-settings-group',
                'bmltwf_enable_debug',
                array(
                    'type' => 'string',
                    'description' => __('Enable debug logging', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_enable_debug_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'false'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_correspondence_page',
                array(
                    'type' => 'string',
                    'description' => __('Correspondence page for email links', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_correspondence_page_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => ''
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_nation',
                array(
                    'type' => 'string',
                    'description' => __('Option to enable displaying nation field to end users', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_location_nation_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'hidden'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_nation_displayname',
                array(
                    'type' => 'string',
                    'description' => __('Display name for nation field', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_textstring_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'Nation'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_sub_province',
                array(
                    'type' => 'string',
                    'description' => __('Option to enable displaying subprovince field to end users', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_location_sub_province_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'hidden'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_sub_province_displayname',
                array(
                    'type' => 'string',
                    'description' => __('Display name for subprovince field', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_textstring_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'Sub Province'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_province',
                array(
                    'type' => 'string',
                    'description' => __('optional field for location_province', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_location_province_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'display'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_location_province_displayname',
                array(
                    'type' => 'string',
                    'description' => __('optional field for location_province', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_textstring_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'Province'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_postcode',
                array(
                    'type' => 'string',
                    'description' => __('optional field for postcode', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_optional_postcode_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'display'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_optional_postcode_displayname',
                array(
                    'type' => 'string',
                    'description' => __('optional field for postcode', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_textstring_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'Postcode'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_required_meeting_formats',
                array(
                    'type' => 'string',
                    'description' => __('required field for meeting format', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_required_meeting_formats_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'true'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_submitter_email_template',
                array(
                    'type' => 'string',
                    'description' => __('Email template for submitter', 'bmlt-workflow'),
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
                    'description' => __('Toggle for FSO feature', 'bmlt-workflow'),
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
                    'description' => __('Email template for FSO', 'bmlt-workflow'),
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
                    'description' => __('FSO email address', 'bmlt-workflow'),
                    'sanitize_callback' => array(&$this, 'bmltwf_fso_email_address_sanitize_callback'),
                    'show_in_rest' => false,
                    'default' => 'example@example.com'
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_correspondence_submitter_email_template',
                array(
                    'type' => 'string',
                    'description' => __('Email template for correspondence notifications to submitters', 'bmlt-workflow'),
                    'sanitize_callback' => null,
                    'show_in_rest' => false,
                    'default' => file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_submitter_email_template.html')
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_correspondence_admin_email_template',
                array(
                    'type' => 'string',
                    'description' => __('Email template for correspondence notifications to admins', 'bmlt-workflow'),
                    'sanitize_callback' => null,
                    'show_in_rest' => false,
                    'default' => file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_admin_email_template.html')
                )
            );

            register_setting(
                'bmltwf-settings-group',
                'bmltwf_admin_notification_email_template',
                array(
                    'type' => 'string',
                    'description' => __('Email template for admin notifications of new submissions', 'bmlt-workflow'),
                    'sanitize_callback' => null,
                    'show_in_rest' => false,
                    'default' => file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_admin_notification_email_template.html')
                )
            );

            // Create separate sections for each tab
            add_settings_section(
                'bmltwf-bmlt-config-section',
                '',
                '',
                'bmltwf-bmlt-config'
            );
            
            add_settings_section(
                'bmltwf-form-settings-section',
                '',
                '',
                'bmltwf-form-settings'
            );
            
            add_settings_section(
                'bmltwf-email-templates-section',
                '',
                '',
                'bmltwf-email-templates'
            );
            
            add_settings_section(
                'bmltwf-advanced-section',
                '',
                '',
                'bmltwf-advanced'
            );

            // BMLT Configuration Tab
            add_settings_field(
                'bmltwf_bmlt_server_address',
                __('BMLT Root Server Configuration', 'bmlt-workflow'),
                array(&$this, 'bmltwf_bmlt_server_address_html'),
                'bmltwf-bmlt-config',
                'bmltwf-bmlt-config-section'
            );

            add_settings_field(
                'bmltwf_geocoding',
                __('Auto Geocoding Root Server Settings', 'bmlt-workflow'),
                array(&$this, 'bmltwf_auto_geocoding_enabled_html'),
                'bmltwf-bmlt-config',
                'bmltwf-bmlt-config-section'
            );

            add_settings_field(
                'bmltwf_google_maps_key',
                __('Google Maps Key', 'bmlt-workflow'),
                array(&$this, 'bmltwf_google_maps_key_html'),
                'bmltwf-bmlt-config',
                'bmltwf-bmlt-config-section'
            );

            // Form Settings Tab
            add_settings_field(
                'bmltwf_shortcode',
                __('Meeting Update Form Shortcode', 'bmlt-workflow'),
                array(&$this, 'bmltwf_shortcode_html'),
                'bmltwf-form-settings',
                'bmltwf-form-settings-section'
            );

            add_settings_field(
                'bmltwf_optional_form_fields',
                __('Optional form fields', 'bmlt-workflow'),
                array(&$this, 'bmltwf_optional_form_fields_html'),
                'bmltwf-form-settings',
                'bmltwf-form-settings-section'
            );

            add_settings_field(
                'bmltwf_fso_options',
                __('Field Service Office configuration', 'bmlt-workflow'),
                array(&$this, 'bmltwf_fso_options_html'),
                'bmltwf-form-settings',
                'bmltwf-form-settings-section'
            );

            add_settings_field(
                'bmltwf_correspondence_page',
                __('Correspondence Page', 'bmlt-workflow'),
                array(&$this, 'bmltwf_correspondence_page_html'),
                'bmltwf-form-settings',
                'bmltwf-form-settings-section'
            );

            // Email Templates Tab
            add_settings_field(
                'bmltwf_email_from_address',
                __('Email From Address', 'bmlt-workflow'),
                array(&$this, 'bmltwf_email_from_address_html'),
                'bmltwf-email-templates',
                'bmltwf-email-templates-section'
            );

            add_settings_field(
                'bmltwf_submitter_email_template',
                __('Email template used when sending a form submission notification', 'bmlt-workflow'),
                array(&$this, 'bmltwf_submitter_email_template_html'),
                'bmltwf-email-templates',
                'bmltwf-email-templates-section'
            );

            add_settings_field(
                'bmltwf_correspondence_email_templates',
                __('Correspondence email templates', 'bmlt-workflow'),
                array(&$this, 'bmltwf_correspondence_email_templates_html'),
                'bmltwf-email-templates',
                'bmltwf-email-templates-section'
            );

            add_settings_field(
                'bmltwf_admin_notification_email_template',
                __('Admin notification email template', 'bmlt-workflow'),
                array(&$this, 'bmltwf_admin_notification_email_template_html'),
                'bmltwf-email-templates',
                'bmltwf-email-templates-section'
            );

            // Advanced Settings Tab
            add_settings_field(
                'bmltwf_delete_closed_meetings',
                __('Default for close meeting submission', 'bmlt-workflow'),
                array(&$this, 'bmltwf_delete_closed_meetings_html'),
                'bmltwf-advanced',
                'bmltwf-advanced-section'
            );

            add_settings_field(
                'bmltwf_trusted_servants_can_delete_submissions',
                __('Trusted servants can delete submissions', 'bmlt-workflow'),
                array(&$this, 'bmltwf_trusted_servants_can_delete_submissions_html'),
                'bmltwf-advanced',
                'bmltwf-advanced-section'
            );

            add_settings_field(
                'bmltwf_remove_virtual_meeting_details_on_venue_change',
                __("Remove Virtual Meeting details when venue is changed to 'face to face'", 'bmlt-workflow'),
                array(&$this, 'bmltwf_remove_virtual_meeting_details_on_venue_change_html'),
                'bmltwf-advanced',
                'bmltwf-advanced-section'
            );

            add_settings_field(
                'bmltwf_backup_restore',
                __('Backup and Restore', 'bmlt-workflow'),
                array(&$this, 'bmltwf_backup_restore_html'),
                'bmltwf-advanced',
                'bmltwf-advanced-section'
            );
            
            add_settings_field(
                'bmltwf_enable_debug',
                __('Enable Debug Logging', 'bmlt-workflow'),
                array(&$this, 'bmltwf_enable_debug_html'),
                'bmltwf-advanced',
                'bmltwf-advanced-section'
            );
            
        }

        public function bmltwf_fso_feature_sanitize_callback($input)
        {
            $output = get_option('bmltwf_fso_feature');
            if (!isset($_POST['bmltwf_fso_feature'])) {
                return $output;
            }
            switch ($input) {
                case 'hidden':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_fso_feature', 'err', __('Invalid FSO Enabled setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_optional_postcode_sanitize_callback($input)
        {
            $output = get_option('bmltwf_optional_postcode');
            if (!isset($_POST['bmltwf_optional_postcode'])) {
                return $output;
            }
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_postcode', 'err', __('Invalid Postcode setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_optional_location_nation_sanitize_callback($input)
        {
            $output = get_option('bmltwf_optional_location_nation');
            if (!isset($_POST['bmltwf_optional_location_nation'])) {
                return $output;
            }
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_location_nation', 'err', __('Invalid Nation setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_optional_location_sub_province_sanitize_callback($input)
        {
            $output = get_option('bmltwf_optional_location_sub_province');
            if (!isset($_POST['bmltwf_optional_location_sub_province'])) {
                return $output;
            }
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_location_sub_province', 'err', __('Invalid Sub Province setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_optional_location_province_sanitize_callback($input)
        {
            $output = get_option('bmltwf_optional_location_province');
            if (!isset($_POST['bmltwf_optional_location_province'])) {
                return $output;
            }
            switch ($input) {
                case 'hidden':
                case 'displayrequired':
                case 'display':
                    return $input;
            }
            add_settings_error('bmltwf_optional_location_province', 'err', __('Invalid Province setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_textstring_sanitize_callback($input)
        {
            return sanitize_text_field($input);
        }

        public function bmltwf_email_from_address_sanitize_callback($input)
        {
            $output = get_option('bmltwf_email_from_address');
            if (!isset($_POST['bmltwf_email_from_address'])) {
                return $output;
            }
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email)) {
                add_settings_error('bmltwf_email_from_address', 'err', __('Invalid email from address.', 'bmlt-workflow'));
                return $output;
            }
            return $sanitized_email;
        }

        public function bmltwf_google_maps_key_sanitize_callback($input)
        {
            $output = get_option('bmltwf_google_maps_key');
            if (!isset($_POST['bmltwf_google_maps_key'])) {
                return $output;
            }
            if ((strlen($input) != 39) && ($input !== "")) {
                add_settings_error('bmltwf_google_maps_key', 'err', __('Invalid google maps key.', 'bmlt-workflow'));
                return $output;
            }
            return $input;
        }

        public function bmltwf_fso_email_address_sanitize_callback($input)
        {
            $output = get_option('bmltwf_fso_email_address');
            if (!isset($_POST['bmltwf_fso_email_address'])) {
                return $output;
            }
            $sanitized_email = sanitize_email($input);
            if (!is_email($sanitized_email)) {
                add_settings_error('bmltwf_fso_email_address', 'err', __('Invalid FSO email address.', 'bmlt-workflow'));
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
            add_settings_error('bmltwf_delete_closed_meetings', 'err', __('Invalid "delete closed meetings" setting.', 'bmlt-workflow'));
            return $output;
        }


        public function bmltwf_required_meeting_formats_sanitize_callback($input)
        {
            $output = get_option('bmltwf_required_meeting_formats');
            if (!isset($_POST['bmltwf_required_meeting_formats'])) {
                return $output;
            }
            switch ($input) {
                case 'true':
                case 'false':
                    return $input;
            }
            add_settings_error('bmltwf_required_meeting_formats', 'err', __('Invalid "meeting formats required" setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_trusted_servants_can_delete_submissions_sanitize_callback($input)
        {
            $output = get_option('bmltwf_trusted_servants_can_delete_submissions');

            switch ($input) {
                case 'true':
                case 'false':
                    return $input;
            }
            add_settings_error('bmltwf_trusted_servants_can_delete_submissions', 'err', __('Invalid "non admins can delete submissions" setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_remove_virtual_meeting_details_on_venue_change_sanitize_callback($input)
        {
            $output = get_option('bmltwf_remove_virtual_meeting_details_on_venue_change');

            switch ($input) {
                case 'true':
                case 'false':
                    return $input;
            }
            add_settings_error('bmltwf_remove_virtual_meeting_details_on_venue_change_sanitize_callback', 'err', __('Invalid "remove virtual meeting details on venue change" setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_bmlt_server_address_html()
        {
            echo '<div id="bmltwf_bmlt_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>';
            echo __('Your BMLT Root Server details are successfully configured.', 'bmlt-workflow');
            echo '</div>';
            echo '<div id="bmltwf_bmlt_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>';
            echo __('Your BMLT Root Server details are not configured correctly.', 'bmlt-workflow');
            echo '</div>';
            echo '<div id="bmltwf_servicebodies_test_yes" style="display: none;" ><span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>';
            echo __('Your service bodies are successfully configured.', 'bmlt-workflow');
            echo '</div>';
            echo '<div id="bmltwf_servicebodies_test_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>';
            echo __('Your service bodies are not configured and saved correctly. <a href="?bmltwf-submissions">Fix</a>', 'bmlt-workflow');
            echo '</div>';
            echo '<div id="bmltwf_server_version_yes" style="display: none;" ></div>';
            echo '<div id="bmltwf_server_version_no" style="display: none;" ><span class="dashicons dashicons-no" style="color: red;"></span>';
            echo __('Cannot retrieve the BMLT Server Version', 'bmlt-workflow');
            echo '</div>';
            echo '<div id="bmltwf_bmlt_server_version"></div>';
            echo '<br>';
            echo '<button type="button" id="bmltwf_configure_bmlt_server">';
            echo __('Update BMLT Root Server Configuration', 'bmlt-workflow');
            echo '</button>';
            echo '<br>';
        }

        public function bmltwf_backup_restore_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('Backup and Restore the entire plugin configuration, including all submission entries and plugin settings.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';
            echo '<br>';
            echo '<button type="button" id="bmltwf_backup">' . __('Backup Configuration', 'bmlt-workflow') . '</button>   <button type="button" id="bmltwf_restore">';
            echo __('Restore Configuration', 'bmlt-workflow');
            echo '</button><input type="file" id="bmltwf_file_selector" accept=".json,application/json" style="display:none">';
            echo '<span class="spinner" id="bmltwf-backup-spinner"></span><br>';
        }

        public function bmltwf_shortcode_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('Use the shortcode <code>[bmltwf-meeting-update-form]</code> to generate a form. The form will be associated with service bodies configured on the Service Bodies configuration page.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';
        }

        public function bmltwf_auto_geocoding_enabled_html()
        {
            $autogeo = $this->bmlt_integration->isAutoGeocodingEnabled('auto');
            if ($autogeo) {
                $val = "true";
                $val1 = __('will', 'bmlt-workflow');
            } else {
                $val = "false";
                $val1 = __('will not', 'bmlt-workflow');
            }
            echo '<div class="bmltwf_info_text" id="bmltwf_auto_geocoding_settings_text">';
            echo '<br>';
            echo __('This plugin honours the BMLT Root Server Auto Geocoding settings. The $auto_geocoding_enabled setting is set to ', 'bmlt-workflow');
            echo '<b>' . $val . '</b>';
            echo '<br><br>';
            echo __('Meeting submissions ', 'bmlt-workflow');
            echo '<b>' . $val1 . '</b> ';
            echo __('be automatically geocoded on save', 'bmlt-workflow');
            echo '<br><br>';

            $autogeo = $this->bmlt_integration->isAutoGeocodingEnabled('zip');
            if ($autogeo) {
                $val = "true";
                $val1 = __('will', 'bmlt-workflow');
            } else {
                $val = "false";
                $val1 = __('will not', 'bmlt-workflow');
            }
            echo __('Zip codes ', 'bmlt-workflow');
            echo '<b>' . $val1 . '</b> ';
            echo __('be automatically added from geocoding results on save', 'bmlt-workflow');
            echo '<br><br>';

            $autogeo = $this->bmlt_integration->isAutoGeocodingEnabled('county');
            if ($autogeo) {
                $val = "true";
                $val1 = __('will', 'bmlt-workflow');
            } else {
                $val = "false";
                $val1 = __('will not', 'bmlt-workflow');
            }
            echo __('County ', 'bmlt-workflow');
            echo '<b>' . $val1 . '</b> ';
            echo __('be automatically added from geocoding results on save', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';
        }

        public function bmltwf_google_maps_key_html()
        {

            $google_maps_key = get_option('bmltwf_google_maps_key');
            $bmlt_key = '';
            $your_own_key = '';
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This plugin will try and use the google maps key from your BMLT Root Server for geolocation and displaying the map view.', 'bmlt-workflow');
            echo '<br>';
            echo __('You can also provide a dedicated google maps key below and this will be used in preference to the BMLT Root Server key.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';
            if ($google_maps_key === '') {
                $bmlt_key = 'selected';
            } else {
                $your_own_key = 'selected';
            }
            echo '<br><label for="bmltwf_google_maps_key_select"></label><select id="bmltwf_google_maps_key_select" name="bmltwf_google_maps_key_select"><option name="bmlt_key" value="bmlt_key" ' . $bmlt_key . '>';
            echo __('Google Maps Key from BMLT', 'bmlt-workflow');
            echo '</option><option name="your_own_key" value="your_own_key" ' . $your_own_key . '>';
            echo __('Custom Google Maps Key', 'bmlt-workflow');
            echo '</option>';
            echo '<br><br>';
            echo '<input id="bmltwf_google_maps_key" type="text" size="39" name="bmltwf_google_maps_key" value="' . esc_attr($google_maps_key) . '"/>';
        }

        public function bmltwf_email_from_address_html()
        {

            $from_address = get_option('bmltwf_email_from_address');
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('The sender (From:) address of meeting update notification emails. Can contain a display name and email in the form <code>Display Name &lt;example@example.com&gt;</code> or just a standard email address.', 'bmlt-workflow');
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
            echo '<br>';
            echo __('Trusted servants approving a "Close Meeting" request can choose to either Delete or Unpublish. This option selects the default for all trusted servants.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_delete_closed_meetings"><b>' . __('Close meeting default', 'bmlt_workflow') . ':</b></label><select id="bmltwf_delete_closed_meetings" name="bmltwf_delete_closed_meetings"><option name="unpublish" value="unpublish" ' . $unpublish . '>';
            echo __('Unpublish', 'bmlt-workflow');
            echo '</option><option name="delete" value="delete" ' . $delete . '>';
            echo __('Delete', 'bmlt-workflow');
            echo '</option>';
            echo '<br><br>';
        }

        public function bmltwf_trusted_servants_can_delete_submissions_html()
        {

            $selection = get_option('bmltwf_trusted_servants_can_delete_submissions');
            $can_delete = '';
            $cannot_delete = '';
            if ($selection === 'true') {
                $can_delete = 'selected';
            } else {
                $cannot_delete = 'selected';
            }

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This option determines whether trusted servants are able to delete submissions from the submissions list.', 'bmlt-workflow');
            echo '<br><br>';
            echo __('If this is set to false, then only Wordpress administrators will have delete submission functionality', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_trusted_servants_can_delete_submissions"><b>';
            echo __('Trusted servants can delete submissions:', 'bmlt-workflow');
            echo '</b></label><select id="bmltwf_trusted_servants_can_delete_submissions" name="bmltwf_trusted_servants_can_delete_submissions"><option name="';
            echo __('True', 'bmlt-workflow');
            echo '" value="true" ' . $can_delete . '>True</option><option name="';
            echo __('False', 'bmlt-workflow');
            echo '" value="false" ' . $cannot_delete . '>False</option>';
            echo '<br><br>';
        }

        public function bmltwf_remove_virtual_meeting_details_on_venue_change_html()
        {
            $selection = get_option('bmltwf_remove_virtual_meeting_details_on_venue_change');
            $do_remove = '';
            $do_not_remove = '';
            if ($selection === 'true') {
                $do_remove = 'selected';
            } else {
                $do_not_remove = 'selected';
            }

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This option determines whether virtual meeting configuration, such as url, extra info and dialin number, are removed when the meeting venue type is changed to a face to face meeting.');
            echo '<br><br>';
            echo __('If this is set to false, the virtual meeting settings will be retained in BMLT.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_remove_virtual_meeting_details_on_venue_change"><b>';
            echo __('Remove virtual meeting details when meetings are changed to face to face', 'bmlt-workflow');
            echo ':</b></label><select id="bmltwf_remove_virtual_meeting_details_on_venue_change" name="bmltwf_remove_virtual_meeting_details_on_venue_change"><option name="';
            echo __('True', 'bmlt-workflow');
            echo '" value="true" ' . $do_remove . '>' . __('True', 'bmlt-workflow') . '</option><option name="';
            echo __('False', 'bmlt-workflow');
            echo '" value="false" ' . $do_not_remove . '>' . __('False', 'bmlt-workflow') . '</option>';
            echo '<br><br>';
        }

        public function bmltwf_optional_form_fields_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('Optional form fields, available depending on how your service bodies use BMLT. These can be displayed, displayed and required, or hidden from your end users. You can also change the way some fields are labelled on the meeting change form.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            echo '<table><thead><tr><th>';
            echo __('BMLT Field Name', 'bmlt-workflow');
            echo '</th><th>';
            echo __('Show on form', 'bmlt-workflow');
            echo '</th><th>';
            echo __('Required Field', 'bmlt-workflow');
            echo '</th><th>';
            echo __('Change displayname to', 'bmlt-workflow');
            echo ':</th></tr></thead><tbody>';
            $this->do_required_field('bmltwf_required_meeting_formats', __('Meeting Formats', 'bmlt-workflow'));
            $this->do_optional_field('bmltwf_optional_location_nation', __('Nation', 'bmlt-workflow'));
            $this->do_optional_field('bmltwf_optional_location_province', __('State/Province', 'bmlt-workflow'));
            $this->do_optional_field('bmltwf_optional_location_sub_province', __('County/Sub-Province', 'bmlt-workflow'));
            $this->do_optional_field('bmltwf_optional_postcode', __('Zip/Postal Code', 'bmlt-workflow'));
            echo '</tbody></table>';
        }

        private function do_required_field($option, $friendlyname)
        {
            echo '<tr>';
            echo '<td>' . $friendlyname . '</td>';
            $value = get_option($option);
            $disabled = '';

            switch ($value) {
                case 'true':
                    echo '<td></td><td><input type="checkbox" id="' . $option . '_required_checkbox" name="' . $option . '_required_checkbox" checked></td>';
                    break;
                case 'false':
                    echo '<td><td><input type="checkbox" id="' . $option . '_required_checkbox" name="' . $option . '_required_checkbox"></td>';
                    break;
            }

            echo '<td></td>';
            echo '</tr>';
            echo '<input type="hidden" name="' . $option . '">';
        }

        private function do_optional_field($option, $friendlyname)
        {
            echo '<tr>';
            echo '<td>' . $friendlyname . '</td>';
            $value = get_option($option);
            $displayname = get_option($option . "_displayname");
            $disabled = '';

            switch ($value) {
                case 'hidden':
                    $disabled = 'disabled';
                    echo '<td><input type="checkbox" id="' . $option . '_visible_checkbox" name="' . $option . '_visible_checkbox" class="bmltwf_optional_visible_checkbox"></td><td><input type="checkbox" id="' . $option . '_required_checkbox" name="' . $option . '_required_checkbox" class="' . $option . '_disable" checked ' . $disabled . '></td>';
                    break;
                case 'displayrequired':
                    echo '<td><input type="checkbox" id="' . $option . '_visible_checkbox" name="' . $option . '_visible_checkbox" class="bmltwf_optional_visible_checkbox" checked></td><td><input type="checkbox" id="' . $option . '_required_checkbox" name="' . $option . '_required_checkbox" class="' . $option . '_disable" checked></td>';
                    break;
                case 'display':
                    echo '<td><input type="checkbox" id="' . $option . '_visible_checkbox" name="' . $option . '_visible_checkbox" class="bmltwf_optional_visible_checkbox" checked></td><td><input type="checkbox" id="' . $option . '_required_checkbox" name="' . $option . '_required_checkbox" class="' . $option . '_disable"></td>';
                    break;
            }

            echo '<td><input type="text" class="' . $option . '_disable" id="' . $option . '_displayname" name="' . $option . '_displayname" value="' . sanitize_text_field($displayname) . ' " ' . $disabled . ' ></td>';
            echo '</tr>';
            echo '<input type="hidden" name="' . $option . '">';
        }

        public function bmltwf_fso_options_html()
        {
            $hidden = '';
            $display = '';

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('Enable this setting to display the starter kit option in the submission form and to configure the email address for your Field Service Office.', 'bmlt-workflow');
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

            echo '<br><label for="bmltwf_fso_feature"><b>';
            echo __('FSO Features', 'bmlt-workflow');
            echo ':</b>';
            echo '</label><select id="bmltwf_fso_feature" name="bmltwf_fso_feature">';
            echo '<option name="hidden" value="hidden" ' . esc_attr($hidden) . '>';
            echo __('Disabled', 'bmlt-workflow');
            echo '</option>';
            echo '<option name="display" value="display" ' . esc_attr($display) . '>';
            echo __('Enabled', 'bmlt-workflow');
            echo '</option>';
            echo '</select>';
            echo '<br><br>';
            echo '<div id="fso_options">';
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('The email address to notify the FSO that starter kits are required.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';


            echo '<br><label for="bmltwf_email_from_address"><b>';
            echo __('FSO Email Address', 'bmlt-workflow');
            echo ':</b></label><input type="text" size="50" id="bmltwf_fso_email_address" name="bmltwf_fso_email_address" value="' . esc_attr($from_address) . '"/>';
            echo '<br><br>';

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This template will be used when emailing the FSO about starter kit requests.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            $content = get_option('bmltwf_fso_email_template');
            $editor_id = 'bmltwf_fso_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">';
            echo __('Copy default template to clipboard', 'bmlt-workflow');
            echo '</button>';
            echo '<br><br>';
            echo '</div>';
        }

        public function bmltwf_submitter_email_template_html()
        {

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __("This template will be used when emailing a submitter about the meeting change they've requested.", 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            $content = get_option('bmltwf_submitter_email_template');
            $editor_id = 'bmltwf_submitter_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">';
            echo __('Copy default template to clipboard', 'bmlt-workflow');
            echo '</button>';
            echo '<br><br>';
        }


        public function display_bmltwf_admin_options_page()
        {
            include_once('admin/admin_options_tabbed.php');
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
            $this->debug_log("is_multisite = " . var_export(is_multisite(), true));
            $this->debug_log("is_plugin_active_for_network = " . var_export(is_plugin_active_for_network(__FILE__), true));
            $this->debug_log("networkwide = " . var_export($networkwide, true));
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

        public function bmltwf_correspondence_email_templates_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('These templates will be used when sending correspondence notifications.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            // Submitter template
            echo '<h4>' . __('Template for notifications to submitters', 'bmlt-workflow') . '</h4>';
            $content = get_option('bmltwf_correspondence_submitter_email_template');
            $editor_id = 'bmltwf_correspondence_submitter_email_template';
            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">';
            echo __('Copy default template to clipboard', 'bmlt-workflow');
            echo '</button>';
            echo '<br><br>';

            // Admin template
            echo '<h4>' . __('Template for notifications to admins', 'bmlt-workflow') . '</h4>';
            $content = get_option('bmltwf_correspondence_admin_email_template');
            $editor_id = 'bmltwf_correspondence_admin_email_template';
            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">';
            echo __('Copy default template to clipboard', 'bmlt-workflow');
            echo '</button>';
            echo '<br><br>';
        }

        public function bmltwf_admin_notification_email_template_html()
        {
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This template will be used when notifying admins about new meeting submissions.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            $content = get_option('bmltwf_admin_notification_email_template');
            $editor_id = 'bmltwf_admin_notification_email_template';

            wp_editor($content, $editor_id, array('media_buttons' => false));
            echo '<button class="clipboard-button" type="button" data-clipboard-target="#' . esc_attr($editor_id) . '_default">';
            echo __('Copy default template to clipboard', 'bmlt-workflow');
            echo '</button>';
            echo '<br><br>';
        }

        private function bmltwf_add_default_options()
        {
            // install all our default options (if they arent set already)
            add_option('bmltwf_email_from_address', 'example@example.com');
            add_option('bmltwf_delete_closed_meetings', 'unpublish');
            add_option('bmltwf_optional_location_nation', 'hidden');
            add_option('bmltwf_optional_location_nation_displayname', __('Nation', 'bmlt-workflow'));
            add_option('bmltwf_optional_location_sub_province', 'hidden');
            add_option('bmltwf_optional_location_sub_province_displayname', __('Sub Province', 'bmlt-workflow'));
            add_option('bmltwf_optional_location_province', 'display');
            add_option('bmltwf_optional_location_province_displayname', __('Province', 'bmlt-workflow'));
            add_option('bmltwf_optional_postcode', 'display');
            add_option('bmltwf_optional_postcode_displayname', __('Postcode', 'bmlt-workflow'));
            add_option('bmltwf_required_meeting_formats', 'true');
            add_option('bmltwf_submitter_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_submitter_email_template.html'));
            add_option('bmltwf_fso_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_fso_email_template.html'));
            add_option('bmltwf_fso_email_address', 'example@example.com');
            add_option('bmltwf_fso_feature', 'display');
            add_option('bmltwf_correspondence_page', '');
            add_option('bmltwf_correspondence_submitter_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_submitter_email_template.html'));
            add_option('bmltwf_correspondence_admin_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_admin_email_template.html'));
            add_option('bmltwf_admin_notification_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_admin_notification_email_template.html'));
        }

        public function bmltwf_add_capability($user_id)
        {
            // give all 'manage_options" users the capability on create so they are able to see the submission menu
            $this->bmltwf_add_capability_to_manage_options_user(get_user_by('id', $user_id));
        }

        private function bmltwf_add_capability_to_manage_options_user($user)
        {
            if ($user->has_cap('manage_options')) {
                $user->add_cap($this->bmltwf_capability_manage_submissions);
                $this->debug_log("adding capabilities to user " . $user->get('ID'));
            }
        }

        public function bmltwf_enable_debug_sanitize_callback($input)
        {
            $output = get_option('bmltwf_enable_debug');

            switch ($input) {
                case 'true':
                case 'false':
                    return $input;
            }
            add_settings_error('bmltwf_enable_debug', 'err', __('Invalid "enable debug" setting.', 'bmlt-workflow'));
            return $output;
        }

        public function bmltwf_correspondence_page_sanitize_callback($input)
        {
            $output = get_option('bmltwf_correspondence_page');
            
            if (empty($input)) {
                return '';
            }
            
            $page_id = intval($input);
            if ($page_id <= 0) {
                add_settings_error('bmltwf_correspondence_page', 'err', __('Invalid correspondence page selection.', 'bmlt-workflow'));
                return $output;
            }
            
            $page = get_post($page_id);
            if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish') {
                add_settings_error('bmltwf_correspondence_page', 'err', __('Selected page does not exist or is not published.', 'bmlt-workflow'));
                return $output;
            }
            
            return $page_id;
        }

        public function bmltwf_enable_debug_html()
        {
            $selection = get_option('bmltwf_enable_debug');
            $debug_enabled = '';
            $debug_disabled = '';
            if ($selection === 'true') {
                $debug_enabled = 'selected';
            } else {
                $debug_disabled = 'selected';
            }

            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('This option enables or disables debug logging for the plugin.');
            echo '<br><br>';
            echo __('If this is set to true, debug information will be written to the database.');
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_enable_debug"><b>';
            echo __('Enable debug logging', 'bmlt-workflow');
            echo ':</b></label><select id="bmltwf_enable_debug" name="bmltwf_enable_debug"><option name="';
            echo __('True', 'bmlt-workflow');
            echo '" value="true" ' . $debug_enabled . '>' . __('True', 'bmlt-workflow') . '</option><option name="';
            echo __('False', 'bmlt-workflow');
            echo '" value="false" ' . $debug_disabled . '>' . __('False', 'bmlt-workflow') . '</option></select>';
            echo '<br><br>';
            
            // Add download button if debug is enabled
            if ($selection === 'true') {
                $today = date('Y-m-d');
                $filename = "bmlt-workflow-debug-{$today}.txt";
                echo '<br><button type="button" id="download_debug_log_button" class="button">' . __('Download Debug Log', 'bmlt-workflow') . '</button>';
                echo '<a id="bmltwf_debug_filename" style="display:none;" download="' . $filename . '"></a>';
            }
        }

        public function bmltwf_correspondence_page_html()
        {
            $selected_page = get_option('bmltwf_correspondence_page');
            
            echo '<div class="bmltwf_info_text">';
            echo '<br>';
            echo __('Select the WordPress page that contains the correspondence form shortcode <code>[bmltwf-correspondence-form]</code>. This page URL will be used in email notifications to submitters when new correspondence is available.', 'bmlt-workflow');
            echo '<br><br>';
            echo '</div>';

            echo '<br><label for="bmltwf_correspondence_page"><b>';
            echo __('Correspondence Page', 'bmlt-workflow');
            echo ':</b></label><select id="bmltwf_correspondence_page" name="bmltwf_correspondence_page">';
            echo '<option value="">' . __('Select a page...', 'bmlt-workflow') . '</option>';
            
            $pages = get_pages(array(
                'post_status' => 'publish',
                'sort_column' => 'post_title'
            ));
            
            $shortcode_pages = array();
            foreach ($pages as $page) {
                if (has_shortcode($page->post_content, 'bmltwf-correspondence-form')) {
                    $shortcode_pages[] = $page;
                }
            }
            
            if (empty($shortcode_pages)) {
                echo '<option value="" disabled>' . __('No pages found with [bmltwf-correspondence-form] shortcode', 'bmlt-workflow') . '</option>';
            } else {
                foreach ($shortcode_pages as $page) {
                    $selected = ($selected_page == $page->ID) ? 'selected' : '';
                    echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>';
                    echo esc_html($page->post_title);
                    echo '</option>';
                }
            }
            
            echo '</select>';
            echo '<br><br>';
        }
    }
    
    load_plugin_textdomain('bmlt-workflow', false, dirname(plugin_basename(__FILE__)) . '/lang');
    $start_plugin = new bmltwf_plugin();
}

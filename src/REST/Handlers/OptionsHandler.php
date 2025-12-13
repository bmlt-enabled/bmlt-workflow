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



namespace bmltwf\REST\Handlers;

use bmltwf\BMLTWF_Database;
use bmltwf\BMLT\Integration;

class OptionsHandler
{
    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\BMLTWF_Constants;
    use \bmltwf\BMLTWF_WP_User;
    use \bmltwf\REST\HandlerCore;

    protected $BMLTWF_Database;
    protected $Integration;

    public function __construct()
    {
        $this->initTableNames();
        
        $this->BMLTWF_Database = new BMLTWF_Database();
        $this->Integration = new Integration();
    }

    public function post_bmltwf_restore_handler($request)
    {
        global $wpdb;
        
    
        $this->debug_log("restore handler called");

        $params = $request->get_json_params();
        $this->debug_log("PARSING PARAMETERS");
        $this->debug_log("Raw body: " . $request->get_body());
        $this->debug_log($params);

        $options = $params['options']??0;
        if (!$options) {
            return $this->bmltwf_rest_error(__('Restore file is missing the options section','bmlt-workflow'), 422);
        }

        // create the database as the revision in the backup file
        $this->BMLTWF_Database->bmltwf_db_upgrade($params['options']['bmltwf_db_version'], true);

        // restore all the options
        foreach ($this->bmltwf_options as $value) {
            $option_name = $value;
            delete_option($value);
            $this->debug_log("deleted option: " . $option_name);
            // check if we have an option in our restore that matches the options array
            if (array_key_exists($option_name, $params['options'])) {
                if($option_name === 'bmltwf_bmlt_password')
                {
                    if(is_serialized($params['options'][$option_name]))
                    {
                        $params['options'][$option_name]=unserialize($params['options'][$option_name]);
                    }
                }
                add_option($option_name, $params['options'][$option_name]);
                $this->debug_log("added option: " . $option_name);
            }
        }
        
        // Ensure correspondence email templates are restored with defaults if missing
        if (!get_option('bmltwf_correspondence_submitter_email_template')) {
            add_option('bmltwf_correspondence_submitter_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_submitter_email_template.html'));
        }
        if (!get_option('bmltwf_correspondence_admin_email_template')) {
            add_option('bmltwf_correspondence_admin_email_template', file_get_contents(BMLTWF_PLUGIN_DIR . 'templates/default_correspondence_admin_email_template.html'));
        }

        // Drop and recreate tables for the backup database version
        $this->BMLTWF_Database->bmltwf_drop_tables();
        $backup_version = $params['options']['bmltwf_db_version'];
        $charset_collate = $wpdb->get_charset_collate();
        $this->BMLTWF_Database->createTables($charset_collate, $backup_version);

        // restore all the tables

        // service bodies table
        $cnt = 0;
        foreach ($params['service_bodies'] as $row => $value) {
            $rows = $wpdb->insert($this->BMLTWF_Database->bmltwf_service_bodies_table_name, $params['service_bodies'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies rows inserted :" . $cnt);

        // service bodies access table
        $cnt = 0;
        foreach ($params['service_bodies_access'] as $row => $value) {
            $wpdb->insert($this->BMLTWF_Database->bmltwf_service_bodies_access_table_name, $params['service_bodies_access'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies_access rows inserted :" . $cnt);

        // submissions table
        $cnt = 0;
        foreach ($params['submissions'] as $row => $value) {
            $rows = $wpdb->insert($this->BMLTWF_Database->bmltwf_submissions_table_name, $params['submissions'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("submissions rows inserted :" . $cnt);

        // correspondence table (if exists in backup)
        $this->debug_log("Checking for correspondence in params. Keys: " . implode(', ', array_keys($params)));
        $this->debug_log("isset correspondence: " . (isset($params['correspondence']) ? 'true' : 'false'));
        $this->debug_log("empty correspondence: " . (empty($params['correspondence']) ? 'true' : 'false'));
        if (isset($params['correspondence']) && !empty($params['correspondence'])) {
            $cnt = 0;
            $this->debug_log("Attempting to restore correspondence table");
            $this->debug_log("Correspondence data: " . print_r($params['correspondence'], true));
            foreach ($params['correspondence'] as $row => $value) {
                $this->debug_log("Inserting correspondence row: " . print_r($params['correspondence'][$row], true));
                $rows = $wpdb->insert($this->BMLTWF_Database->bmltwf_correspondence_table_name, $params['correspondence'][$row]);
                if ($rows === false) {
                    $this->debug_log("Failed to insert correspondence row. Error: " . $wpdb->last_error);
                } else {
                    $this->debug_log("Successfully inserted correspondence row. Rows affected: " . $rows);
                }
                $cnt += $rows;
            }
            $this->debug_log("correspondence rows inserted total: " . $cnt);
        } else {
            $this->debug_log("No correspondence data found in backup or correspondence is empty");
        }

        // Set auto increment to highest ID value + 1
        if (!empty($params['submissions'])) {
            $backup_version = $params['options']['bmltwf_db_version'];
            $id_field = version_compare($backup_version, '1.1.18', '<') ? 'id' : 'change_id';
            
            $max_id = 0;
            foreach ($params['submissions'] as $submission) {
                if (isset($submission->$id_field) && $submission->$id_field > $max_id) {
                    $max_id = $submission->$id_field;
                }
            }
            
            if ($max_id > 0) {
                $next_id = $max_id + 1;
                $wpdb->query("ALTER TABLE " . $this->BMLTWF_Database->bmltwf_submissions_table_name . " AUTO_INCREMENT = $next_id");
                $this->debug_log("Set auto increment to: " . $next_id);
            }
        }

        // update the database to the latest version
        $this->BMLTWF_Database->bmltwf_db_upgrade($this->BMLTWF_Database->bmltwf_db_version, false);
        $uids = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name, 0);

        $this->add_remove_caps($uids);
        // clean out the google maps key if cached
        \update_option('bmltwf_bmlt_google_maps_key', '');

        $this->Integration->update_root_server_version();

        return $this->bmltwf_rest_success(__('Restore Successful','bmlt-workflow'));
    }

    public function post_bmltwf_backup_handler($request)
    {
        
        $this->debug_log("backup handler called");

        global $wpdb;

        $save = array();
        // get options
        $optarr = \wp_load_alloptions();
        $this->debug_log(($optarr));

        $saveoptarr = array();
        foreach ($optarr as $key => $value) {

            if (in_array($key, $this->bmltwf_options)) {
                $this->debug_log("found " . $key);
                $saveoptarr[$key] = $value;
            }
        }
        $save['options'] = $saveoptarr;

        // get submissions
        $result = $wpdb->get_results("SELECT * from " . $this->BMLTWF_Database->bmltwf_submissions_table_name);
        $save['submissions'] = $result;

        // get service bodies
        $result = $wpdb->get_results("SELECT * from " . $this->BMLTWF_Database->bmltwf_service_bodies_table_name);
        $save['service_bodies'] = $result;

        // get service bodies access
        $result = $wpdb->get_results("SELECT * from " . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name);
        $save['service_bodies_access'] = $result;
        
        // get correspondence (if table exists)
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->BMLTWF_Database->bmltwf_correspondence_table_name}'") == $this->BMLTWF_Database->bmltwf_correspondence_table_name) {
            $result = $wpdb->get_results("SELECT * from " . $this->BMLTWF_Database->bmltwf_correspondence_table_name);
            $save['correspondence'] = $result;
        }
        
        $contents = json_encode($save, JSON_PRETTY_PRINT);
        $this->debug_log('backup file generated');
        $this->debug_log($contents);
        $dateTime = new \DateTime();
        $fname = $dateTime->format(\DateTimeInterface::RFC3339_EXTENDED);
        $save['backupdetails'] = $fname;
        return $this->bmltwf_rest_success(array('message' => __('Backup Successful','bmlt-workflow'), 'backup' => $contents));
    }
    
    public function post_bmltwf_debug_handler($request)
    {
        global $bmltwf_debug_enabled, $wpdb;
        
        $this->debug_log("debug log handler called");
        
        // Log the download action with plugin version and options
        $this->debug_log("Debug log file downloaded by user ID: " . get_current_user_id());
        
        // Add plugin version and configuration info to debug log
        $this->debug_log("Plugin Version: " . BMLTWF_PLUGIN_VERSION);
        $this->debug_log("PHP Version: " . phpversion());
        $this->debug_log("BMLT Server Version: " . get_option('bmltwf_bmlt_server_version', 'Unknown'));
        
        // Get all bmltwf options except password
        $optarr = \wp_load_alloptions();
        $config_info = array();
        foreach ($optarr as $key => $value) {
            if (in_array($key, $this->bmltwf_options) && $key !== 'bmltwf_bmlt_password') {
                $config_info[$key] = $value;
            }
        }
        $this->debug_log("Plugin Configuration: " . print_r($config_info, true));
        
        // Check if debug mode is enabled
        if (!defined('BMLTWF_DEBUG') || !BMLTWF_DEBUG) {
            if (!$bmltwf_debug_enabled) {
                // Debug mode is not enabled, add a log entry to explain this
                $this->log_to_database('post_bmltwf_debug_handler', 'Debug mode is not enabled. Enable it in settings to capture logs.');
            }
        }
        
        // Check if debug log table exists
        $debug_log_table = $wpdb->prefix . 'bmltwf_debug_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '$debug_log_table'") != $debug_log_table) {
            // Create the debug log table if it doesn't exist
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $debug_log_table (
                log_id bigint(20) NOT NULL AUTO_INCREMENT,
                log_time datetime(6) DEFAULT CURRENT_TIMESTAMP(6) NOT NULL,
                log_caller varchar(255) NOT NULL,
                log_message text NOT NULL,
                PRIMARY KEY (log_id)
            ) $charset_collate;";
            
            $wpdb->query($sql);
            
            // Create index on log_time for faster cleanup
            $wpdb->query("CREATE INDEX idx_log_time ON $debug_log_table(log_time);");
            
            // Add an initial log entry
            $this->log_to_database('post_bmltwf_debug_handler', 'Debug log table created');
        }
        
        // Get logs from database
        $logs = $this->get_debug_logs(5000, 0); // Get all logs up to 5000
        
        if (empty($logs)) {
            // Add a test log entry if no logs found
            $this->log_to_database('post_bmltwf_debug_handler', 'No logs found. This is a test log entry.');
            
            // Try to get logs again
            $logs = $this->get_debug_logs(5000, 0);
            
            if (empty($logs)) {
                return $this->bmltwf_rest_error(__('No debug logs found in database. Make sure debug mode is enabled in settings.', 'bmlt-workflow'), 404);
            }
        }
        
        // Build log content
        $log_content = '';
        foreach (array_reverse($logs) as $log) {
            $log_content .= "[{$log->log_time}] {$log->log_caller}: {$log->log_message}\n";
        }
        
        // Return the log content
        return $this->bmltwf_rest_success(array(
            'message' => __('Debug log download successful', 'bmlt-workflow'),
            'log_content' => base64_encode($log_content)
        ));
    }
    
    public function post_bmltwf_correspondence_page_handler($request)
    {
        $this->debug_log("correspondence page handler called");
        
        $params = $request->get_json_params();
        $page_id = $params['page_id'] ?? '';
        
        if (empty($page_id)) {
            update_option('bmltwf_correspondence_page', '');
            return $this->bmltwf_rest_success(__('Correspondence page cleared successfully', 'bmlt-workflow'));
        }
        
        $page_id = intval($page_id);
        if ($page_id <= 0) {
            return $this->bmltwf_rest_error(__('Invalid page ID', 'bmlt-workflow'), 400);
        }
        
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish') {
            return $this->bmltwf_rest_error(__('Selected page does not exist or is not published', 'bmlt-workflow'), 400);
        }
        
        update_option('bmltwf_correspondence_page', $page_id);
        
        return $this->bmltwf_rest_success(__('Correspondence page updated successfully', 'bmlt-workflow'));
    }
}

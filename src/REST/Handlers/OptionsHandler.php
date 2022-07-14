<?php

namespace wbw\REST\Handlers;

use wbw\REST\HandlerCore;
use wbw\WBW_Database;
use wbw\WBW_WP_Options;

class OptionsHandler
{
    use \wbw\WBW_Debug;

    public function __construct()
    {
        $this->handlerCore = new HandlerCore();
        $this->WBW_Database = new WBW_Database();
        $this->WBW_WP_Options = new WBW_WP_Options();
    }

    public function post_wbw_restore_handler($request)
    {
        global $wpdb;
        
    
        $this->debug_log("restore handler called");
        // $this->debug_log(($request));

        $params = $request->get_json_params();

        // create the database as the revision in the backup file
        $this->WBW_Database->wbw_db_upgrade($params['options']['wbw_db_version'], true);

        // restore all the options
        foreach ($this->WBW_WP_Options->wbw_options as $key => $value) {
            $option_name = $value;
            delete_option($this->WBW_WP_Options->wbw_options[$option_name]);
            $this->debug_log("deleted option: " . $option_name);
            // check if we have an option in our restore that matches the options array
            if (array_key_exists($option_name, $params['options'])) {
                if($option_name === 'wbw_bmlt_password')
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

        // restore all the tables

        // service bodies table
        $cnt = 0;
        foreach ($params['service_bodies'] as $row => $value) {
            $rows = $wpdb->insert($this->WBW_Database->wbw_service_bodies_table_name, $params['service_bodies'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies rows inserted :" . $cnt);

        // service bodies access table
        $cnt = 0;
        foreach ($params['service_bodies_access'] as $row => $value) {
            $wpdb->insert($this->WBW_Database->wbw_service_bodies_access_table_name, $params['service_bodies_access'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies_access rows inserted :" . $cnt);

        // submissions table
        $cnt = 0;
        foreach ($params['submissions'] as $row => $value) {
            $rows = $wpdb->insert($this->WBW_Database->wbw_submissions_table_name, $params['submissions'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("submissions rows inserted :" . $cnt);


        // update the database to the latest version
        $this->WBW_Database->wbw_db_upgrade($this->WBW_Database->wbw_db_version, false);

        return $this->handlerCore->wbw_rest_success('Restore Successful');
    }

    public function post_wbw_backup_handler($request)
    {
        

        $this->debug_log("backup handler called");

        global $wpdb;

        $save = array();
        // get options
        $optarr = \wp_load_alloptions();
        $this->debug_log(($optarr));

        $saveoptarr = array();
        foreach ($optarr as $key => $value) {
            $this->debug_log("searching for " . $key . " in ");
            $this->debug_log(($this->WBW_WP_Options->wbw_options));

            if (in_array($key, $this->WBW_WP_Options->wbw_options)) {
                $this->debug_log("found " . $key);
                $saveoptarr[$key] = $value;
            }
        }
        $save['options'] = $saveoptarr;

        // get submissions
        $result = $wpdb->get_results("SELECT * from " . $this->WBW_Database->wbw_submissions_table_name);
        $save['submissions'] = $result;

        // get service bodies
        $result = $wpdb->get_results("SELECT * from " . $this->WBW_Database->wbw_service_bodies_table_name);
        $save['service_bodies'] = $result;

        // get service bodies access
        $result = $wpdb->get_results("SELECT * from " . $this->WBW_Database->wbw_service_bodies_access_table_name);
        $save['service_bodies_access'] = $result;
        $contents = json_encode($save, JSON_PRETTY_PRINT);
        $this->debug_log('backup file generated');
        $this->debug_log($contents);
        $dateTime = new \DateTime();
        $fname = $dateTime->format(\DateTimeInterface::RFC3339_EXTENDED);
        $save['backupdetails'] = $fname;
        return $this->handlerCore->wbw_rest_success(array('message' => 'Backup Successful', 'backup' => $contents));
    }
}

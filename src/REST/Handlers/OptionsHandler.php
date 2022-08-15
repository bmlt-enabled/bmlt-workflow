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



namespace bw\REST\Handlers;

use bw\REST\HandlerCore;
use bw\BW_Database;
use bw\BW_WP_Options;

class OptionsHandler
{
    use \bw\BW_Debug;

    public function __construct()
    {
        $this->handlerCore = new HandlerCore();
        $this->BW_Database = new BW_Database();
        $this->BW_WP_Options = new BW_WP_Options();
    }

    public function post_bw_restore_handler($request)
    {
        global $wpdb;
        
    
        $this->debug_log("restore handler called");
        // $this->debug_log(($request));

        $params = $request->get_json_params();

        // create the database as the revision in the backup file
        $this->BW_Database->bw_db_upgrade($params['options']['bw_db_version'], true);

        // restore all the options
        foreach ($this->BW_WP_Options->bw_options as $key => $value) {
            $option_name = $value;
            delete_option($this->BW_WP_Options->bw_options[$option_name]);
            $this->debug_log("deleted option: " . $option_name);
            // check if we have an option in our restore that matches the options array
            if (array_key_exists($option_name, $params['options'])) {
                if($option_name === 'bw_bmlt_password')
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
            $rows = $wpdb->insert($this->BW_Database->bw_service_bodies_table_name, $params['service_bodies'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies rows inserted :" . $cnt);

        // service bodies access table
        $cnt = 0;
        foreach ($params['service_bodies_access'] as $row => $value) {
            $wpdb->insert($this->BW_Database->bw_service_bodies_access_table_name, $params['service_bodies_access'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("service_bodies_access rows inserted :" . $cnt);

        // submissions table
        $cnt = 0;
        foreach ($params['submissions'] as $row => $value) {
            $rows = $wpdb->insert($this->BW_Database->bw_submissions_table_name, $params['submissions'][$row]);
            $cnt += $rows;
        }
        $this->debug_log("submissions rows inserted :" . $cnt);


        // update the database to the latest version
        $this->BW_Database->bw_db_upgrade($this->BW_Database->bw_db_version, false);

        return $this->handlerCore->bw_rest_success('Restore Successful');
    }

    public function post_bw_backup_handler($request)
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
            $this->debug_log(($this->BW_WP_Options->bw_options));

            if (in_array($key, $this->BW_WP_Options->bw_options)) {
                $this->debug_log("found " . $key);
                $saveoptarr[$key] = $value;
            }
        }
        $save['options'] = $saveoptarr;

        // get submissions
        $result = $wpdb->get_results("SELECT * from " . $this->BW_Database->bw_submissions_table_name);
        $save['submissions'] = $result;

        // get service bodies
        $result = $wpdb->get_results("SELECT * from " . $this->BW_Database->bw_service_bodies_table_name);
        $save['service_bodies'] = $result;

        // get service bodies access
        $result = $wpdb->get_results("SELECT * from " . $this->BW_Database->bw_service_bodies_access_table_name);
        $save['service_bodies_access'] = $result;
        $contents = json_encode($save, JSON_PRETTY_PRINT);
        $this->debug_log('backup file generated');
        $this->debug_log($contents);
        $dateTime = new \DateTime();
        $fname = $dateTime->format(\DateTimeInterface::RFC3339_EXTENDED);
        $save['backupdetails'] = $fname;
        return $this->handlerCore->bw_rest_success(array('message' => 'Backup Successful', 'backup' => $contents));
    }
}

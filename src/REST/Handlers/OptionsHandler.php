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
        // $this->debug_log("OptionsHandler: Creating new BMLTWF_Database");        
        $this->BMLTWF_Database = new BMLTWF_Database();
        // $this->debug_log("OptionsHandler: Creating new Integration");        
        $this->Integration = new Integration();
    }

    public function post_bmltwf_restore_handler($request)
    {
        global $wpdb;
        
    
        $this->debug_log("restore handler called");
        // $this->debug_log(($request));

        $params = $request->get_json_params();
        $this->debug_log("PARSING PARAMETERS");
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
            $this->debug_log("searching for " . $key . " in ");
            $this->debug_log(($this->bmltwf_options));

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
        $contents = json_encode($save, JSON_PRETTY_PRINT);
        $this->debug_log('backup file generated');
        $this->debug_log($contents);
        $dateTime = new \DateTime();
        $fname = $dateTime->format(\DateTimeInterface::RFC3339_EXTENDED);
        $save['backupdetails'] = $fname;
        return $this->bmltwf_rest_success(array('message' => __('Backup Successful','bmlt-workflow'), 'backup' => $contents));
    }
}

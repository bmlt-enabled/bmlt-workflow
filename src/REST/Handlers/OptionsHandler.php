<?php

namespace wbw\REST\Handlers;

use wbw\REST\HandlerCore;

class OptionsHandler
{

    public function __construct()
    {
        $this->handlerCore = new HandlerCore;
    }

    public function post_wbw_restore_handler($request)
    {
        global $wpdb;
        global $wbw_dbg;
        global $wbw_db_version;
        global $wbw_options;
        global $wbw_submissions_table_name;
        global $wbw_service_bodies_table_name;
        global $wbw_service_bodies_access_table_name;

        $wbw_dbg->debug_log("restore handler called");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $params = $request->get_json_params();

        // create the database as the revision in the backup file
        wbw_db_upgrade($params['options']['wbw_db_version'], true);

        // restore all the options
        foreach ($wbw_options as $key => $value) {
            $option_name = $value;
            delete_option($wbw_options[$option_name]);
            $wbw_dbg->debug_log("deleted option: " . $option_name);
            // check if we have an option in our restore that matches the options array
            if (array_key_exists($option_name, $params['options'])) {
                // if($option_name == $wbw_options['wbw_bmlt_password'])
                // {
                //     $wbw_dbg->debug_log("decrypting " . $option_name);
                //     $value = $this->handlerCore->secrets_decrypt(NONCE_SALT, unserialize($params['options'][$option_name]));
                //     // $wbw_dbg->debug_log("decrypting to " . $value);
                //     add_option($option_name, $value);
                // }
                // else
                // {
                    add_option($option_name, $params['options'][$option_name]);

                // }
                $wbw_dbg->debug_log("added option: " . $option_name);
            }
        }

        // restore all the tables

        // service bodies table
        $cnt = 0;
        foreach ($params['service_bodies'] as $row => $value) {
            $rows = $wpdb->insert($wbw_service_bodies_table_name, $params['service_bodies'][$row]);
            $cnt += $rows;
        }
        $wbw_dbg->debug_log("service_bodies rows inserted :" . $cnt);

        // service bodies access table
        $cnt = 0;
        foreach ($params['service_bodies_access'] as $row => $value) {
            $wpdb->insert($wbw_service_bodies_access_table_name, $params['service_bodies_access'][$row]);
            $cnt += $rows;
        }
        $wbw_dbg->debug_log("service_bodies_access rows inserted :" . $cnt);

        // submissions table
        $cnt = 0;
        foreach ($params['submissions'] as $row => $value) {
            $rows = $wpdb->insert($wbw_submissions_table_name, $params['submissions'][$row]);
            $cnt += $rows;
        }
        $wbw_dbg->debug_log("submissions rows inserted :" . $cnt);


        // update the database to the latest version
        wbw_db_upgrade($wbw_db_version, false);

        return $this->handlerCore->wbw_rest_success('Restore Successful');
    }

    public function post_wbw_backup_handler($request)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("backup handler called");

        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_service_bodies_table_name;
        global $wbw_service_bodies_access_table_name;
        global $wbw_options;

        $save = array();
        // get options
        $optarr = wp_load_alloptions();
        $saveoptarr = array();
        foreach ($optarr as $key => $value) {
            $found = array_search($key, $wbw_options);

            if ($found == true) {
                $wbw_dbg->debug_log("found " . $key);
                // if($key == $wbw_options['wbw_bmlt_password'])
                // {
                //     // $wbw_dbg->debug_log("encrypting " . $value);
                //     $value = serialize($this->handlerCore->secrets_encrypt(NONCE_SALT, $value));
                //     $wbw_dbg->debug_log("encrypted to " . $value);
                // }
                $saveoptarr[$key] = $value;
            }
        }
        $save['options'] = $saveoptarr;

        // get submissions
        $result = $wpdb->get_results("SELECT * from " . $wbw_submissions_table_name);
        $save['submissions'] = $result;

        // get service bodies
        $result = $wpdb->get_results("SELECT * from " . $wbw_service_bodies_table_name);
        $save['service_bodies'] = $result;

        // get service bodies access
        $result = $wpdb->get_results("SELECT * from " . $wbw_service_bodies_access_table_name);
        $save['service_bodies_access'] = $result;
        $contents = json_encode($save, JSON_PRETTY_PRINT);
        $wbw_dbg->debug_log('backup file generated');
        $wbw_dbg->debug_log($contents);
        $dateTime = new \DateTime();
        $fname = $dateTime->format(\DateTimeInterface::RFC3339_EXTENDED);
        $save['backupdetails'] = $fname;
        return $this->handlerCore->wbw_rest_success(array('message' => 'Backup Successful', 'backup' => $contents));
    }
}

<?php 

namespace wbw\REST\Handlers;

use wbw\REST\HandlerCore;
class OptionsHandler
{

    public function __construct()
    {
        $this->handlerCore = new HandlerCore;
    }

    public function post_wbw_backup_handler($request)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("backup handler called");

        global $wpdb;
        global $wbw_db_version;
        global $wbw_submissions_table_name;
        global $wbw_service_bodies_table_name;
        global $wbw_service_bodies_access_table_name;
        global $wbw_options;
        $charset_collate = $wpdb->get_charset_collate();
    
        $save = array();
        // get options
        $optarr = wp_load_alloptions();
        $saveoptarr = array();
        foreach ($optarr as $key => $value)
        {
            if(array_key_exists($key, $wbw_options))
            {
                $wbw_dbg->debug_log("found ".$key);
                $saveoptarr[$key]=$value;
            }
        }
        $save['options']=$saveoptarr;

        // get submissions
        $result = $wpdb->get_results("SELECT * from ". $wbw_submissions_table_name);
        $save['submissions']=$result;

        // get service bodies
        $result = $wpdb->get_results("SELECT * from ". $wbw_service_bodies_table_name);
        $save['service_bodies']=$result;

        // get service bodies access
        $result = $wpdb->get_results("SELECT * from ". $wbw_service_bodies_access_table_name);
        $save['service_bodies_access']=$result;

        $wbw_dbg->debug_log(json_encode($save, JSON_PRETTY_PRINT));

        // 
        // require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
        // $sql = "CREATE TABLE " . $wbw_service_bodies_table_name . " (
        //     service_body_bigint bigint(20) NOT NULL,
        //     service_body_name tinytext NOT NULL,
        //     service_body_description text,
        //     contact_email varchar(255) NOT NULL default '',
        //     show_on_form bool,
        //     PRIMARY KEY (service_body_bigint)
        // ) $charset_collate;";
    
        // // dbDelta($sql);
        // $wpdb->query($sql);
    
        // $sql = "CREATE TABLE " . $wbw_service_bodies_access_table_name . " (
        //     service_body_bigint bigint(20) NOT NULL,
        //     wp_uid bigint(20) unsigned  NOT NULL,
        //     FOREIGN KEY (service_body_bigint) REFERENCES " . $wbw_service_bodies_table_name . "(service_body_bigint) 
        // ) $charset_collate;";
    
        // // dbDelta($sql);
        // $wpdb->query($sql);
    
        // $sql = "CREATE TABLE " . $wbw_submissions_table_name . " (
        //     id bigint(20) NOT NULL AUTO_INCREMENT,
        //     submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        //     change_time datetime DEFAULT '0000-00-00 00:00:00',
        //     changed_by varchar(10),
        //     change_made varchar(10),
        //     submitter_name tinytext NOT NULL,
        //     submission_type tinytext NOT NULL,
        //     submitter_email varchar(320) NOT NULL,
        //     meeting_id bigint(20) unsigned,
        //     service_body_bigint bigint(20) NOT NULL,
        //     changes_requested varchar(2048),
        //     action_message varchar(1024),
        //     PRIMARY KEY (id),
        //     FOREIGN KEY (service_body_bigint) REFERENCES " . $wbw_service_bodies_table_name . "(service_body_bigint) 
        // ) $charset_collate;";
    
        // // dbDelta($sql);
        // $wpdb->query($sql);
    
        // add_option('wbw_db_version', $wbw_db_version);
    

        return $this->handlerCore->wbw_rest_success('Backup completed.');

    }
}
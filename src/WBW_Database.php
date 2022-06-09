<?php

namespace wbw;

class WBW_Database
{

    public function __construct($stub = null)
    {
        global $wpdb;

        $this->wbw_db_version = '0.4.0';

        // database tables
        $this->wbw_submissions_table_name = $wpdb->prefix . 'wbw_submissions';
        $this->wbw_service_bodies_table_name = $wpdb->prefix . 'wbw_service_bodies';
        $this->wbw_service_bodies_access_table_name = $wpdb->prefix . 'wbw_service_bodies_access';
        $this->wbw_dbg = new WBW_Debug();
    }


    public function wbw_db_upgrade($desired_version, $fresh_install)
    {
        $WP_Options = new WP_Options();


        // work out which version we're at right now
        $installed_version = $WP_Options->wbw_get_option('wbw_db_version');

        // do nothing by default
        $upgrade = false;

        if ($installed_version === false) {
            // fresh install
            $fresh_install = true;
            $this->wbw_dbg->debug_log("no db version found, performing fresh install");
        } else {
            if (version_compare($desired_version, $installed_version, 'eq')) {
                $this->wbw_dbg->debug_log("doing nothing - installed db version " . $installed_version . " is same as desired version " . $desired_version);
            } else {
                $upgrade = true;
                $this->wbw_dbg->debug_log("db version = " . $installed_version . " - requesting upgrade");
            }
        }

        if ($fresh_install) {
            $this->wbw_dbg->debug_log("fresh install");

            // latest version
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();

            // shouldn't need this but just in case the tables already exist
            $sql = "DROP TABLE " . $this->wbw_service_bodies_access_table_name . ";";
            $wpdb->query($sql);
            $sql = "DROP TABLE " . $this->wbw_submissions_table_name . ";";
            $wpdb->query($sql);
            $sql = "DROP TABLE " . $this->wbw_service_bodies_table_name . ";";
            $wpdb->query($sql);
            $this->wbw_dbg->debug_log("fresh install: tables dropped");

            $sql = "CREATE TABLE " . $this->wbw_service_bodies_table_name . " (
            service_body_bigint bigint(20) NOT NULL,
            service_body_name tinytext NOT NULL,
            service_body_description text,
            show_on_form bool,
            PRIMARY KEY (service_body_bigint)
        ) $charset_collate;";

            $wpdb->query($sql);

            $sql = "CREATE TABLE " . $this->wbw_service_bodies_access_table_name . " (
            service_body_bigint bigint(20) NOT NULL,
            wp_uid bigint(20) unsigned  NOT NULL,
            FOREIGN KEY (service_body_bigint) REFERENCES " . $this->wbw_service_bodies_table_name . "(service_body_bigint) 
        ) $charset_collate;";

            $wpdb->query($sql);

            $sql = "CREATE TABLE " . $this->wbw_submissions_table_name . " (
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
            FOREIGN KEY (service_body_bigint) REFERENCES " . $this->wbw_service_bodies_table_name . "(service_body_bigint) 
        ) $charset_collate;";

            $wpdb->query($sql);

            $this->wbw_dbg->debug_log("fresh install: tables created");

            delete_option('wbw_db_version');
            add_option('wbw_db_version', $this->WBW_Database->wbw_db_version);
            $this->wbw_dbg->debug_log("fresh install: db version installed");

            return;
        }

        if ($upgrade) {
            delete_option('wbw_db_version');
            add_option('wbw_db_version', $this->WBW_Database->wbw_db_version);
            return;
        }
    }
}

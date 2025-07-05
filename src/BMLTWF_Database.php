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

namespace bmltwf;

class BMLTWF_Database
{
    use \bmltwf\BMLTWF_Debug;
    public $bmltwf_db_version = '1.1.18';
    public $bmltwf_submissions_table_name;
    public $bmltwf_service_bodies_table_name;
    public $bmltwf_service_bodies_access_table_name;

    public function __construct($stub = null)
    {
        global $wpdb;
        // database tables
        $this->bmltwf_submissions_table_name = $wpdb->prefix . 'bmltwf_submissions';
        $this->bmltwf_service_bodies_table_name = $wpdb->prefix . 'bmltwf_service_bodies';
        $this->bmltwf_service_bodies_access_table_name = $wpdb->prefix . 'bmltwf_service_bodies_access';
    }

    public function bmltwf_drop_tables()
    {
        global $wpdb;

        $sql = "DROP TABLE IF EXISTS " . $this->bmltwf_service_bodies_access_table_name . ";";
        $wpdb->query($sql);
        $sql = "DROP TABLE IF EXISTS " . $this->bmltwf_submissions_table_name . ";";
        $wpdb->query($sql);
        $sql = "DROP TABLE IF EXISTS " . $this->bmltwf_service_bodies_table_name . ";";
        $wpdb->query($sql);
        $this->debug_log("tables dropped");

    }
    
    /**
     * bmltwf_db_upgrade
     *
     * @param  mixed $desired_version
     * @param  mixed $fresh_install
     * @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed
     */
    public function bmltwf_db_upgrade($desired_version, $fresh_install)
    {
        global $wpdb;

        $installed_version = get_option('bmltwf_db_version');
        
        if ($installed_version === false || $this->checkTablesExist() < 3) {
            return $this->performFreshInstall();
        }

        if (version_compare($desired_version, $installed_version, 'eq')) {
            $this->debug_log("doing nothing - installed db version " . $installed_version . " is same as desired version " . $desired_version);
            return 0;
        }

        return $this->performUpgrade($installed_version);
    }

    private function checkTablesExist()
    {
        global $wpdb;
        $tables = [$this->bmltwf_service_bodies_access_table_name, $this->bmltwf_submissions_table_name, $this->bmltwf_service_bodies_table_name];
        $count = 0;
        
        foreach ($tables as $table) {
            $sql = 'SHOW TABLES LIKE "' . $table . '"';
            $wpdb->query($sql);
            $count += $wpdb->num_rows;
        }
        
        return $count;
    }

    private function performFreshInstall()
    {
        global $wpdb;
        
        $this->debug_log("fresh install");
        $charset_collate = $wpdb->get_charset_collate();
        $this->bmltwf_drop_tables();
        $this->createTables($charset_collate);
        
        delete_option('bmltwf_db_version');
        add_option('bmltwf_db_version', $this->bmltwf_db_version);
        $this->debug_log("fresh install: db version installed");
        
        return 1;
    }

    private function createTables($charset_collate)
    {
        global $wpdb;
        
        $sql = "CREATE TABLE " . $this->bmltwf_service_bodies_table_name . " (
            serviceBodyId bigint(20) NOT NULL,
            service_body_name tinytext NOT NULL,
            service_body_description text,
            show_on_form bool,
            PRIMARY KEY (serviceBodyId)
        ) $charset_collate;";
        $wpdb->query($sql);

        $sql = "CREATE TABLE " . $this->bmltwf_service_bodies_access_table_name . " (
            serviceBodyId bigint(20) NOT NULL,
            wp_uid bigint(20) unsigned  NOT NULL,
            FOREIGN KEY (serviceBodyId) REFERENCES " . $this->bmltwf_service_bodies_table_name . "(serviceBodyId) 
        ) $charset_collate;";
        $wpdb->query($sql);

        $sql = "CREATE TABLE " . $this->bmltwf_submissions_table_name . " (
            change_id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            change_time datetime NULL DEFAULT NULL,
            changed_by varchar(10),
            change_made varchar(10),
            submitter_name tinytext NOT NULL,
            submission_type tinytext NOT NULL,
            submitter_email varchar(320) NOT NULL,
            id bigint(20) unsigned,
            serviceBodyId bigint(20) NOT NULL,
            changes_requested varchar(2048),
            action_message varchar(1024),
            PRIMARY KEY (change_id),
            FOREIGN KEY (serviceBodyId) REFERENCES " . $this->bmltwf_service_bodies_table_name . "(serviceBodyId) 
        ) $charset_collate;";
        $wpdb->query($sql);
        
        $this->debug_log("fresh install: tables created");
    }

    private function performUpgrade($installed_version)
    {
        global $wpdb;
        
        delete_option('bmltwf_db_version');
        add_option('bmltwf_db_version', $this->bmltwf_db_version);

        if (version_compare($installed_version, '1.1.18', '<')) {
            $this->upgradeTableStructure();
            $this->upgradeJsonFields();
        }

        
        return 2;
    }
    private function fixDateTimeColumns()
    {
        global $wpdb;
        
        // Temporarily disable strict mode
        $wpdb->query("SET sql_mode = ''");
        
        // Fix invalid datetime values
        $wpdb->query("UPDATE " . $this->bmltwf_submissions_table_name . " SET submission_time = '1970-01-01 00:00:01' WHERE submission_time = '0000-00-00 00:00:00'");
        $wpdb->query("UPDATE " . $this->bmltwf_submissions_table_name . " SET change_time = NULL WHERE change_time = '0000-00-00 00:00:00'");
        
        // Re-enable strict mode
        $wpdb->query("SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        
        // Fix datetime columns and auto-increment
        $sql = "ALTER TABLE " . $this->bmltwf_submissions_table_name . " 
                MODIFY COLUMN submission_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                MODIFY COLUMN change_time datetime NULL DEFAULT NULL,
                MODIFY COLUMN change_id bigint(20) NOT NULL AUTO_INCREMENT,
                AUTO_INCREMENT = 1402";
        $wpdb->query($sql);
        
        $this->debug_log("datetime columns and auto-increment fixed");
    }

    private function upgradeTableStructure()
    {
        global $wpdb;
        
        $alterQueries = [
            "ALTER TABLE " . $this->bmltwf_service_bodies_table_name . " CHANGE COLUMN service_body_bigint serviceBodyId bigint(20) NOT NULL",
            "ALTER TABLE " . $this->bmltwf_service_bodies_access_table_name . " CHANGE COLUMN service_body_bigint serviceBodyId bigint(20) NOT NULL",
            "ALTER TABLE " . $this->bmltwf_submissions_table_name . " CHANGE COLUMN service_body_bigint serviceBodyId bigint(20) NOT NULL",
            "ALTER TABLE " . $this->bmltwf_submissions_table_name . " CHANGE COLUMN id change_id bigint(20) unsigned NOT NULL AUTO_INCREMENT",
            "ALTER TABLE " . $this->bmltwf_submissions_table_name . " CHANGE COLUMN meeting_id id bigint(20) unsigned"
        ];
        
        foreach ($alterQueries as $sql) {
            $wpdb->query($sql);
        }

        $this->fixDateTimeColumns();

        $this->debug_log("table structure upgraded");
    }

    private function upgradeJsonFields()
    {
        global $wpdb;
        
        // Simple string replacements
        $replacements = [
            'original_start_time' => 'original_startTime',
            'start_time' => 'startTime',
            'original_duration_time' => 'original_duration',
            'duration_time' => 'duration',
            'meeting_name' => 'name',
            'original_meeting_name' => 'original_name',
            'original_service_body_bigint' => 'original_serviceBodyId',
            'service_body_bigint' => 'serviceBodyId'
        ];
        
        foreach ($replacements as $old => $new) {
            $sql = "UPDATE " . $this->bmltwf_submissions_table_name . " 
                    SET changes_requested = REPLACE(changes_requested, '\"$old\":', '\"$new\":')
                    WHERE changes_requested LIKE '%$old%'";
            $wpdb->query($sql);
        }
        
        // Complex transformations
        $this->upgradeComplexJsonFields();
        
        $this->debug_log("JSON fields upgraded");
    }

    private function upgradeComplexJsonFields()
    {
        global $wpdb;
        
        $results = $wpdb->get_results("SELECT change_id, changes_requested FROM " . $this->bmltwf_submissions_table_name);
        
        foreach ($results as $row) {
            $json_data = json_decode($row->changes_requested, true);
            $updated = false;
            
            // Convert weekday fields
            if (isset($json_data['weekday_tinyint'])) {
                $json_data['day'] = intval($json_data['weekday_tinyint']) - 1;
                unset($json_data['weekday_tinyint']);
                $updated = true;
            }
            
            if (isset($json_data['original_weekday_tinyint'])) {
                $json_data['original_day'] = intval($json_data['original_weekday_tinyint']) - 1;
                unset($json_data['original_weekday_tinyint']);
                $updated = true;
            }
            
            // Convert format fields to arrays
            if (isset($json_data['format_shared_id_list']) && is_string($json_data['format_shared_id_list'])) {
                $json_data['formatIds'] = array_map('intval', explode(',', $json_data['format_shared_id_list']));
                unset($json_data['format_shared_id_list']);
                $updated = true;
            }

            if (isset($json_data['original_format_shared_id_list']) && is_string($json_data['original_format_shared_id_list'])) {
                $json_data['original_formatIds'] = array_map('intval', explode(',', $json_data['original_format_shared_id_list']));
                unset($json_data['original_format_shared_id_list']);
                $updated = true;
            }

            // Convert time fields to HH:MM
            $time_fields = ['original_startTime', 'original_duration', 'duration', 'startTime'];
            foreach ($time_fields as $field) {
                if (isset($json_data[$field]) && is_string($json_data[$field]) && strlen($json_data[$field]) > 5) {
                    $json_data[$field] = substr($json_data[$field], 0, 5);
                    $updated = true;
                }
            }
            
            if ($updated) {
                $wpdb->update(
                    $this->bmltwf_submissions_table_name,
                    array('changes_requested' => json_encode($json_data)),
                    array('change_id' => $row->change_id)
                );
            }
        }
    }
}
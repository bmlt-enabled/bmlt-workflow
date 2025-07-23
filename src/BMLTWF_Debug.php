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

trait BMLTWF_Debug 
{
    /**
     * Log a debug message to the WordPress error log and to the database
     * 
     * @param mixed $message The message to log
     */
    public function debug_log($message)
    {
        global $bmltwf_debug_enabled;
        
        if (BMLTWF_DEBUG || $bmltwf_debug_enabled) {
            $out = print_r($message, true);
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            
            // Log to WordPress error log
            error_log("$caller: $out");
            
            // Log to database
            $this->log_to_database($caller, $out);
        }
    }
    
    /**
     * Log HTTP error response details
     * 
     * @param array|\WP_Error $response The WordPress HTTP response or error
     */
    public function debug_http_error($response)
    {
        global $bmltwf_debug_enabled;
        
        if (BMLTWF_DEBUG || $bmltwf_debug_enabled) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            
            if (is_wp_error($response)) {
                $error_message = "WP_Error: " . $response->get_error_message();
                $this->debug_log($error_message);
                return;
            }
            
            $response_code = \wp_remote_retrieve_response_code($response);
            
            // Only log details for 5xx server errors
            if ($response_code >= 500) {
                $body = \wp_remote_retrieve_body($response);
                $headers = \wp_remote_retrieve_headers($response);
                
                $error_details = "HTTP $response_code Error:\n";
                $error_details .= "Response Body: $body\n";
                $error_details .= "Response Headers: " . print_r($headers, true);
                
                $this->debug_log($error_details);
            }
        }
    }

    /**
     * Log BMLT payload information for debugging
     * 
     * @param string|null $url The URL being called
     * @param string|null $method The HTTP method being used
     * @param mixed|null $body The request body
     */
    public function debug_bmlt_payload($url=null,$method=null,$body=null)
    {
        global $bmltwf_debug_enabled;
        
        if (BMLTWF_DEBUG || $bmltwf_debug_enabled) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            
            // Log URL
            $out = print_r($url, true);
            error_log("$caller: BMLT Payload : URL - $out");
            
            // Log method
            $out = print_r($method ? $method : 'GET', true);
            error_log("$caller: BMLT Payload : Method - $out");
            
            // Log body
            if (!is_string($body)) {
                $body = json_encode($body);
            }
            $out = print_r($body ? $body : '(null)', true);
            error_log("$caller: BMLT Payload : Body - $out");
            
            // Log to database
            $payload = "BMLT Payload:\n";
            $payload .= "URL: $url\n";
            $payload .= "Method: " . ($method ? $method : 'GET') . "\n";
            $payload .= "Body: " . ($body ? $body : '(null)');
            
            $this->log_to_database($caller, $payload);
        }
    }
    
    /**
     * Write a log entry to the database
     * 
     * @param string $caller The function that called the log method
     * @param string $message The log message
     */
    public function log_to_database($caller, $message)
    {
        global $wpdb;
        
        // Get the debug log table name
        $debug_log_table = $wpdb->prefix . 'bmltwf_debug_log';
        
        // Check if table exists, if not, try to create it
        if ($wpdb->get_var("SHOW TABLES LIKE '$debug_log_table'") != $debug_log_table) {
            // Table doesn't exist, but we can't create it here as we might not have the right permissions
            // Just log to error_log and return
            error_log("BMLTWF: Debug log table doesn't exist.");
            return;
        }
        
        // Get current time with microseconds for higher precision
        $microtime = microtime(true);
        $timestamp = date('Y-m-d H:i:s', (int)$microtime) . '.' . sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
        
        // Insert the log entry with microsecond precision timestamp
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $debug_log_table (log_time, log_caller, log_message) VALUES (%s, %s, %s)",
            $timestamp,
            $caller,
            $message
        ));
        
        // Limit to 5000 entries by removing oldest entries
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $debug_log_table");
        if ($count > 5000) {
            $to_delete = $count - 5000;
            $wpdb->query("DELETE FROM $debug_log_table ORDER BY log_time ASC LIMIT $to_delete");
        }
    }
    
    /**
     * Get debug logs from the database
     * 
     * @param int $limit Maximum number of logs to retrieve (default 100)
     * @param int $offset Offset for pagination (default 0)
     * @return array Array of log entries
     */
    public function get_debug_logs($limit = 100, $offset = 0)
    {
        global $wpdb;
        
        $debug_log_table = $wpdb->prefix . 'bmltwf_debug_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$debug_log_table'") != $debug_log_table) {
            return array();
        }
        
        // Get logs ordered by newest first with microsecond precision
        // Note: MySQL's DATE_FORMAT doesn't handle microseconds properly with %f, so we need to use a different approach
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT log_id, log_time, log_caller, log_message FROM $debug_log_table ORDER BY log_time DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
        
        return $logs;
    }
    
    /**
     * Clear all debug logs from the database
     */
    public function clear_debug_logs()
    {
        global $wpdb;
        
        $debug_log_table = $wpdb->prefix . 'bmltwf_debug_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$debug_log_table'") != $debug_log_table) {
            return false;
        }
        
        // Truncate the table
        return $wpdb->query("TRUNCATE TABLE $debug_log_table");
    }
}

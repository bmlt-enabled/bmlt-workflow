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

namespace bmltwf\REST;


trait HandlerCore
{
    use \bmltwf\BMLTWF_Constants;
    
    public $bmltwf_submissions_table_name;
    public $bmltwf_service_bodies_table_name;
    public $bmltwf_service_bodies_access_table_name;
    public $bmltwf_debug_log_table_name;
    public $bmltwf_correspondence_table_name;
    
    // Initialize table names
    public function initTableNames()
    {
        global $wpdb;
        // database tables
        $this->bmltwf_submissions_table_name = $wpdb->prefix . 'bmltwf_submissions';
        $this->bmltwf_service_bodies_table_name = $wpdb->prefix . 'bmltwf_service_bodies';
        $this->bmltwf_service_bodies_access_table_name = $wpdb->prefix . 'bmltwf_service_bodies_access';
        $this->bmltwf_debug_log_table_name = $wpdb->prefix . 'bmltwf_debug_log';
        $this->bmltwf_correspondence_table_name = $wpdb->prefix . 'bmltwf_correspondence';
    }

    // accepts raw string or array
    public function bmltwf_rest_success($message)
    {
        if (is_array($message)) {
            $data = $message;
        } else {
            $data = array('message' => $message);
        }
        $response = new \WP_REST_Response();
        $response->set_data($data);
        $response->set_status(200);
        return $response;
    }

    // accepts raw string or array
    public function bmltwf_rest_failure($message)
    {
        if (is_array($message)) {
            $data = $message;
        } else {
            $data = array('message' => $message);
        }
        $response = new \WP_REST_Response();
        $response->set_data($data);
        $response->set_status(422);
        return $response;
    }

    public function bmltwf_rest_error($message, $code)
    {
        return new \WP_Error('bmltwf_error', $message, array('status' => $code));
    }

    public function bmltwf_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('bmltwf_error', $message, $data);
    }

    /**
     * Check if user has permission to access a submission
     *
     * @param int $change_id
     * @return object|\WP_Error
     */
    public function get_submission_with_permission_check($change_id)
    {
        global $wpdb;

        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        
        if (current_user_can('manage_options')) {
            // Admin can access all submissions
            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->bmltwf_submissions_table_name} WHERE change_id = %d",
                $change_id
            );
        } else {
            // Non-admin users can only access submissions for service bodies they manage
            $sql = $wpdb->prepare(
                "SELECT s.* FROM {$this->bmltwf_submissions_table_name} s 
                 INNER JOIN {$this->bmltwf_service_bodies_access_table_name} a 
                 ON s.serviceBodyId = a.serviceBodyId 
                 WHERE a.wp_uid = %d AND s.change_id = %d",
                $current_uid,
                $change_id
            );
        }
        
        $result = $wpdb->get_row($sql);
        
        if (!$result) {
            return $this->bmltwf_rest_error(__('Permission denied: You cannot access this submission', 'bmlt-workflow'), 403);
        }
        
        return $result;
    }
}

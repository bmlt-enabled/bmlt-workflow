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

class CorrespondenceHandler
{
    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\REST\HandlerCore;

    public function __construct($stub = null)
    {
        $this->initTableNames();
    }

    /**
     * Get correspondence for a submission
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public function get_correspondence_handler(\WP_REST_Request $request)
    {
        global $wpdb;

        $change_id = $request->get_param('change_id');
        if (!$change_id) {
            return new \WP_Error('rest_invalid_param', __('Invalid change_id parameter', 'bmlt-workflow'), array('status' => 400));
        }

        // Check if the submission exists
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->bmltwf_submissions_table_name} WHERE change_id = %d",
            $change_id
        ));

        if (!$submission) {
            return new \WP_Error('rest_not_found', __('Submission not found', 'bmlt-workflow'), array('status' => 404));
        }

        // Get correspondence for this submission
        $correspondence = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->bmltwf_correspondence_table_name} 
             WHERE change_id = %d 
             ORDER BY created_at ASC",
            $change_id
        ));

        return array(
            'submission' => $submission,
            'correspondence' => $correspondence
        );
    }

    /**
     * Get correspondence by thread ID (for public access)
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public function get_correspondence_by_thread_handler(\WP_REST_Request $request)
    {
        global $wpdb;

        $thread_id = $request->get_param('thread_id');
        if (!$thread_id) {
            return new \WP_Error('rest_invalid_param', __('Invalid thread_id parameter', 'bmlt-workflow'), array('status' => 400));
        }

        // Get correspondence for this thread
        $correspondence = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, s.submitter_email, s.submitter_name 
             FROM {$this->bmltwf_correspondence_table_name} c
             JOIN {$this->bmltwf_submissions_table_name} s ON c.change_id = s.change_id
             WHERE c.thread_id = %s 
             ORDER BY c.created_at ASC",
            $thread_id
        ));

        if (empty($correspondence)) {
            return new \WP_Error('rest_not_found', __('Correspondence thread not found', 'bmlt-workflow'), array('status' => 404));
        }

        // Get the submission details
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT change_id, submission_type, submitter_name, submitter_email, submission_time 
             FROM {$this->bmltwf_submissions_table_name} 
             WHERE change_id = %d",
            $correspondence[0]->change_id
        ));

        return array(
            'submission' => $submission,
            'correspondence' => $correspondence
        );
    }

    /**
     * Add a new correspondence message
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public function post_correspondence_handler(\WP_REST_Request $request)
    {
        global $wpdb;

        $change_id = $request->get_param('change_id');
        $message = $request->get_param('message');
        $from_submitter = $request->get_param('from_submitter') === 'true';
        $thread_id = $request->get_param('thread_id');

        if (!$change_id) {
            return new \WP_Error('rest_invalid_param', __('Invalid change_id parameter', 'bmlt-workflow'), array('status' => 400));
        }

        if (!$message) {
            return new \WP_Error('rest_invalid_param', __('Message cannot be empty', 'bmlt-workflow'), array('status' => 400));
        }

        // Check if the submission exists
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->bmltwf_submissions_table_name} WHERE change_id = %d",
            $change_id
        ));

        if (!$submission) {
            return new \WP_Error('rest_not_found', __('Submission not found', 'bmlt-workflow'), array('status' => 404));
        }

        // If no thread_id provided, create a new one
        if (!$thread_id) {
            $thread_id = wp_generate_uuid4();
        } else {
            // Verify thread_id belongs to this submission
            $existing_thread = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->bmltwf_correspondence_table_name} 
                 WHERE thread_id = %s AND change_id = %d",
                $thread_id,
                $change_id
            ));

            if (!$existing_thread) {
                return new \WP_Error('rest_invalid_param', __('Invalid thread_id for this submission', 'bmlt-workflow'), array('status' => 400));
            }
        }

        // Insert the new correspondence
        $result = $wpdb->insert(
            $this->bmltwf_correspondence_table_name,
            array(
                'change_id' => $change_id,
                'thread_id' => $thread_id,
                'message' => $message,
                'from_submitter' => $from_submitter ? 1 : 0,
                'created_at' => current_time('mysql'),
                'created_by' => $from_submitter ? $submission->submitter_name : wp_get_current_user()->display_name
            )
        );

        if (!$result) {
            return new \WP_Error('rest_error', __('Failed to add correspondence', 'bmlt-workflow'), array('status' => 500));
        }
        
        // Update the submission status based on who sent the correspondence
        $status = $from_submitter ? 'correspondence_received' : 'correspondence_sent';
        $current_user = wp_get_current_user();
        $username = $from_submitter ? $submission->submitter_name : $current_user->user_login;
        $current_time = current_time('mysql');
        
        // Debug log before update
        $before_update = $wpdb->get_row($wpdb->prepare(
            "SELECT change_made FROM {$this->bmltwf_submissions_table_name} WHERE change_id = %d",
            $change_id
        ));
        $this->debug_log("Before update, status was: " . ($before_update ? $before_update->change_made : 'unknown'));
        
        // Use direct SQL query to ensure the update works
        $sql = $wpdb->prepare(
            "UPDATE {$this->bmltwf_submissions_table_name} 
             SET change_made = %s, changed_by = %s, change_time = %s 
             WHERE change_id = %d",
            $status,
            $username,
            $current_time,
            $change_id
        );
        
        $this->debug_log("SQL query: {$sql}");
        $update_result = $wpdb->query($sql);
        
        // Debug log after update
        $after_update = $wpdb->get_row($wpdb->prepare(
            "SELECT change_made FROM {$this->bmltwf_submissions_table_name} WHERE change_id = %d",
            $change_id
        ));
        $this->debug_log("After update, status is: " . ($after_update ? $after_update->change_made : 'unknown'));
        $this->debug_log("Updated submission status to {$status} for change_id {$change_id}, result: {$update_result}");

        // If this is from admin, send email to submitter with link to view correspondence
        if (!$from_submitter) {
            $this->send_correspondence_notification($submission, $thread_id);
        }

        return array(
            'success' => true,
            'thread_id' => $thread_id,
            'message' => __('Correspondence added successfully', 'bmlt-workflow')
        );
    }

    /**
     * Send email notification to submitter about new correspondence
     *
     * @param object $submission
     * @param string $thread_id
     * @return void
     */
    private function send_correspondence_notification($submission, $thread_id)
    {
        $submitter_email = $submission->submitter_email;
        $submitter_name = $submission->submitter_name;
        
        if (!$submitter_email) {
            return;
        }

        $site_url = get_site_url();
        $correspondence_url = $site_url . '/bmlt-workflow-correspondence/?thread=' . $thread_id;
        
        $subject = __('New correspondence about your meeting submission', 'bmlt-workflow');
        
        $message = sprintf(
            __('Hello %s,

There is new correspondence regarding your meeting submission. 

To view and respond to this correspondence, please visit:
%s

Thank you,
%s', 'bmlt-workflow'),
            $submitter_name,
            $correspondence_url,
            get_bloginfo('name')
        );
        
        $from_email = get_option('bmltwf_email_from_address', get_bloginfo('admin_email'));
        $from_name = get_bloginfo('name');
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        wp_mail($submitter_email, $subject, $message, $headers);
    }
}
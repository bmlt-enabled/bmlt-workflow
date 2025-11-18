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

use bmltwf\BMLT\Integration;
use bmltwf\BMLTWF_Email;

class CorrespondenceHandler
{
    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\REST\HandlerCore;
    
    protected $bmlt_integration;
    protected $email;

    public function __construct($stub = null)
    {
        $this->initTableNames();
        $this->bmlt_integration = new Integration();
        $this->email = new BMLTWF_Email();
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

        // Check if the submission exists and user has permission to access it
        $submission = $this->get_submission_with_permission_check($change_id);
        if (is_wp_error($submission)) {
            return $submission;
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
            "SELECT c.*, s.submitter_name, s.change_made 
             FROM {$this->bmltwf_correspondence_table_name} c
             JOIN {$this->bmltwf_submissions_table_name} s ON c.change_id = s.change_id
             WHERE c.thread_id = %s 
             ORDER BY c.created_at ASC",
            $thread_id
        ));

        if (empty($correspondence)) {
            return new \WP_Error('rest_not_found', __('Correspondence thread not found', 'bmlt-workflow'), array('status' => 404));
        }
        
        // Check if submission has been approved/rejected/deleted
        $status = $correspondence[0]->change_made;
        if (in_array($status, ['approved', 'rejected', 'deleted'])) {
            return new \WP_Error('rest_forbidden', __('This correspondence thread is no longer available', 'bmlt-workflow'), array('status' => 403));
        }
        
        // Check if it's been 3 months since the last message
        $last_message = end($correspondence);
        $last_message_time = strtotime($last_message->created_at);
        $three_months_ago = strtotime('-3 months');
        
        if ($last_message_time < $three_months_ago) {
            return new \WP_Error('rest_forbidden', __('This correspondence thread has expired', 'bmlt-workflow'), array('status' => 403));
        }

        // Get the submission details
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT change_id, submission_type, submitter_name, submission_time, id 
             FROM {$this->bmltwf_submissions_table_name} 
             WHERE change_id = %d",
            $correspondence[0]->change_id
        ));
        
        // Get meeting name from BMLT if we have a meeting ID
        if (!empty($submission->id)) {
            $meeting = $this->bmlt_integration->getMeeting($submission->id);
            if (!is_wp_error($meeting) && isset($meeting['name'])) {
                $submission->meeting_name = $meeting['name'];
            } else {
                $submission->meeting_name = "(Unknown Meeting)";
            }
        }

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

        // Check if the submission exists and user has permission to access it
        $submission = $this->get_submission_with_permission_check($change_id);
        if (is_wp_error($submission)) {
            return $submission;
        }

        // Check if submission is approved or rejected - correspondence not allowed
        if (in_array($submission->change_made, ['approved', 'rejected'])) {
            return new \WP_Error('rest_forbidden', __('Correspondence is not allowed for approved or rejected submissions', 'bmlt-workflow'), array('status' => 403));
        }

        // If no thread_id provided, create a new one
        if (!$thread_id) {
            $thread_id = wp_generate_uuid4();
            $this->debug_log("Generated new thread_id: " . $thread_id);
        } else {
            // Verify thread_id belongs to this submission
            $existing_thread = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->bmltwf_correspondence_table_name} 
                 WHERE thread_id = %s AND change_id = %d",
                $thread_id,
                $change_id
            ));

            $this->debug_log("Existing thread check for thread_id {$thread_id}: " . $existing_thread);
            
            if (!$existing_thread) {
                return new \WP_Error('rest_invalid_param', __('Invalid thread_id for this submission', 'bmlt-workflow'), array('status' => 400));
            }
        }
        
        // Validate data before insert
        if (empty($change_id) || !is_numeric($change_id)) {
            $this->debug_log("Invalid change_id: " . $change_id);
            return new \WP_Error('rest_invalid_param', __('Invalid change_id', 'bmlt-workflow'), array('status' => 400));
        }
        
        if (empty($thread_id) || strlen($thread_id) > 36) {
            $this->debug_log("Invalid thread_id: " . $thread_id . " (length: " . strlen($thread_id) . ")");
            return new \WP_Error('rest_invalid_param', __('Invalid thread_id', 'bmlt-workflow'), array('status' => 400));
        }
        
        if (empty($message) || strlen($message) > 65535) {
            $this->debug_log("Invalid message length: " . strlen($message));
            return new \WP_Error('rest_invalid_param', __('Message is empty or too long', 'bmlt-workflow'), array('status' => 400));
        }

        // Prepare data for insert with debug logging
        $current_user = wp_get_current_user();
        $created_by = $from_submitter ? $submission->submitter_name : $current_user->display_name;
        $created_at = current_time('mysql');
        
        $insert_data = array(
            'change_id' => $change_id,
            'thread_id' => $thread_id,
            'message' => $message,
            'from_submitter' => $from_submitter ? 1 : 0,
            'created_at' => $created_at,
            'created_by' => $created_by
        );
        
        $this->debug_log("Attempting to insert correspondence with data: " . json_encode($insert_data));
        $this->debug_log("Table name: " . $this->bmltwf_correspondence_table_name);
        $this->debug_log("Current user ID: " . $current_user->ID . ", display_name: " . $current_user->display_name);
        $this->debug_log("Submission data: " . json_encode($submission));
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $this->bmltwf_correspondence_table_name . "'");
        $this->debug_log("Table exists check: " . ($table_exists ? 'YES' : 'NO'));
        
        // Check table structure
        $table_structure = $wpdb->get_results("DESCRIBE " . $this->bmltwf_correspondence_table_name);
        $this->debug_log("Table structure: " . json_encode($table_structure));
        
        // Insert the new correspondence
        $result = $wpdb->insert(
            $this->bmltwf_correspondence_table_name,
            $insert_data
        );

        $this->debug_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
        $this->debug_log("wpdb->last_error: " . $wpdb->last_error);
        $this->debug_log("wpdb->last_query: " . $wpdb->last_query);
        $this->debug_log("wpdb->insert_id: " . $wpdb->insert_id);
        
        if (!$result) {
            $error_msg = 'Failed to add correspondence. Error: ' . $wpdb->last_error;
            $this->debug_log($error_msg);
            return new \WP_Error('rest_error', __($error_msg, 'bmlt-workflow'), array('status' => 500));
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
            $this->send_correspondence_notification($submission, $thread_id, $message);
        } else {
            // If this is from submitter, check if we need to notify admins
            $this->check_and_send_admin_notification($submission, $thread_id, $change_id, $message);
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
    private function send_correspondence_notification($submission, $thread_id, $last_message = '')
    {
        $submitter_email = $submission->submitter_email;
        $submitter_name = $submission->submitter_name;
        
        if (!$submitter_email) {
            return;
        }

        // Get the configured correspondence page URL
        $correspondence_page_id = get_option('bmltwf_correspondence_page');
        if (!$correspondence_page_id) {
            return; // No correspondence page configured
        }
        
        $correspondence_page_url = get_permalink($correspondence_page_id);
        if (!$correspondence_page_url) {
            return; // Invalid page ID
        }
        
        $correspondence_url = add_query_arg('thread', $thread_id, $correspondence_page_url);
        
        // Get meeting data for email templates
        $meeting_data = null;
        if (!empty($submission->id)) {
            $meeting_data = $this->bmlt_integration->getMeeting($submission->id);
            if (is_wp_error($meeting_data)) {
                $meeting_data = null;
            }
        }
        
        $context = array(
            'change_id' => $submission->change_id,
            'submitter_name' => $submitter_name
        );
        
        $additional = array(
            'correspondence_url' => $correspondence_url,
            'last_correspondence' => $last_message
        );
        
        $this->email->send_correspondence_submitter_notification($submitter_email, $context, $additional, $meeting_data);
        
        $this->debug_log("Correspondence notification email sent - to: {$submitter_email}, url: {$correspondence_url}");
    }

    /**
     * Check if admin notification should be sent for new user correspondence
     *
     * @param object $submission
     * @param string $thread_id
     * @param int $change_id
     * @return void
     */
    private function check_and_send_admin_notification($submission, $thread_id, $change_id, $last_message = '')
    {
        global $wpdb;
        
        // Get all messages in this thread ordered by creation time
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT from_submitter, created_at FROM {$this->bmltwf_correspondence_table_name} 
             WHERE thread_id = %s 
             ORDER BY created_at ASC",
            $thread_id
        ));
        
        if (empty($messages)) {
            return;
        }
        
        // Check if this is the first message from user, or first message from user after admin response
        $should_notify = false;
        $last_message_from_admin = false;
        
        // If this is the only message (first message), notify admins
        if (count($messages) === 1) {
            $should_notify = true;
        } else {
            // Check if the previous message was from admin
            $previous_messages = array_slice($messages, 0, -1); // All except the last (current) message
            $last_previous_message = end($previous_messages);
            
            if ($last_previous_message && !$last_previous_message->from_submitter) {
                $should_notify = true;
            }
        }
        
        if ($should_notify) {
            $this->send_admin_correspondence_notification($submission, $change_id, $last_message);
        }
    }

    /**
     * Send email notification to admins about new correspondence from user
     *
     * @param object $submission
     * @param int $change_id
     * @return void
     */
    private function send_admin_correspondence_notification($submission, $change_id, $last_message = '')
    {
        // Get admin emails for this service body
        $to_address = $this->get_emails_by_servicebody_id($submission->serviceBodyId);
        
        if (empty($to_address)) {
            return;
        }
        
        // Get meeting data for email templates
        $meeting_data = null;
        if (!empty($submission->id)) {
            $meeting_data = $this->bmlt_integration->getMeeting($submission->id);
            if (is_wp_error($meeting_data)) {
                $meeting_data = null;
            }
        }
        
        $context = array(
            'change_id' => $change_id,
            'submitter_name' => $submission->submitter_name
        );
        
        $additional = array(
            'last_correspondence' => $last_message
        );
        
        $this->email->send_correspondence_admin_notification($to_address, $context, $additional, $meeting_data);
        
        $this->debug_log("Admin correspondence notification email sent - to: {$to_address}");
    }

    /**
     * Get emails by service body ID
     *
     * @param int $id
     * @return string
     */
    private function get_emails_by_servicebody_id($id)
    {
        global $wpdb;

        $emails = array();
        $sql = $wpdb->prepare('SELECT wp_uid from ' . $this->bmltwf_service_bodies_access_table_name . ' where serviceBodyId="%d"', $id);
        $result = $wpdb->get_col($sql);
        foreach ($result as $key => $value) {
            $user = get_user_by('ID', $value);
            if ($user) {
                $emails[] = $user->user_email;
            }
        }
        return implode(',', $emails);
    }

}
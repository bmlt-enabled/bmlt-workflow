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

class BMLTWF_Email
{
    use BMLTWF_Debug;

    /**
     * Build common template fields
     *
     * @param array $context Context data (submission, change_id, etc.)
     * @param array $meeting_data Meeting data from BMLT
     * @return array
     */
    private function build_common_fields($context, $meeting_data = null)
    {
        $fields = array(
            'site_name' => get_bloginfo('name'),
            'admin_url' => get_site_url() . '/wp-admin/admin.php?page=bmltwf-submissions'
        );
        
        // Add meeting data if provided
        if ($meeting_data && is_array($meeting_data)) {
            foreach ($meeting_data as $key => $value) {
                $fields['meeting_' . $key] = $value;
            }
            // Also add common meeting fields without prefix for backward compatibility
            if (isset($meeting_data['name'])) $fields['meeting_name'] = $meeting_data['name'];
            if (isset($meeting_data['day'])) $fields['meeting_day'] = $meeting_data['day'];
            if (isset($meeting_data['startTime'])) $fields['meeting_start_time'] = $meeting_data['startTime'];
        }
        
        if (isset($context['change_id'])) {
            $fields['change_id'] = $context['change_id'];
        }
        
        if (isset($context['submitter_name'])) {
            $fields['submitter_name'] = $context['submitter_name'];
        }
        
        if (isset($context['submitter_email'])) {
            $fields['submitter_email'] = $context['submitter_email'];
        }
        
        if (isset($context['submission_type'])) {
            $fields['submission_type'] = $context['submission_type'];
        }
        
        if (isset($context['service_body_name'])) {
            $fields['service_body_name'] = $context['service_body_name'];
        }
        
        if (isset($context['submission_time'])) {
            $fields['submission_time'] = $context['submission_time'];
        }
        
        if (isset($context['name'])) {
            $fields['name'] = $context['name'];
        }
        
        return $fields;
    }

    /**
     * Send email with template substitution
     *
     * @param string $to_address
     * @param string $subject_template
     * @param string $body_template
     * @param array $template_fields
     * @param string $default_subject
     * @return bool
     */
    public function send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject = '')
    {
        $subject = $this->substitute_template_fields($subject_template ?: $default_subject, $template_fields);
        $body = $this->substitute_template_fields($body_template, $template_fields);
        
        $from_address = get_option('bmltwf_email_from_address', get_bloginfo('admin_email'));
        
        // Check if from_address already contains display name format
        if (preg_match('/^(.+)\s*<(.+)>$/', $from_address, $matches)) {
            $from_header = $from_address; // Use as-is if already formatted
        } else {
            $from_name = get_bloginfo('name');
            $from_header = $from_name . ' <' . $from_address . '>';
        }
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_header
        );
        
        $this->debug_log("Sending email - to: {$to_address}, subject: {$subject}");
        $this->debug_log("Email body -  {$body}");
        
        return \wp_mail($to_address, $subject, $body, $headers);
    }

    /**
     * Send admin notification email
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_admin_notification($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_admin_notification_email_subject');
        $body_template = get_option('bmltwf_admin_notification_email_template');
        $submission_type = $template_fields['submission_type'] ?? 'Submission';
        $service_body_name = $template_fields['service_body_name'] ?? 'Service Body';
        $change_id = $template_fields['change_id'] ?? 'Unknown';
        $default_subject = '[bmlt-workflow] ' . $submission_type . ' ' . __('request received','bmlt-workflow') . ' - ' . $service_body_name . ' - ' . __('Change ID','bmlt_workflow') . ' #' . $change_id;
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send submitter acknowledgement email
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_submitter_acknowledgement($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_submitter_email_subject');
        $body_template = get_option('bmltwf_submitter_email_template');
        $default_subject = __('NA Meeting Change Request Acknowledgement - Submission ID','bmlt-workflow') . ' ' . $template_fields['change_id'];
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send approval email
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_approval_email($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_approval_email_subject');
        $body_template = get_option('bmltwf_approval_email_template');
        $default_subject = __('Meeting Change Request Approved - Submission ID','bmlt-workflow') . ' ' . $template_fields['change_id'];
        
        // If no custom template, use default body with action message
        if (empty($body_template)) {
            $body_template = __('Your meeting change has been approved - change ID','bmlt-workflow') . ' ({field:change_id})';
            if (!empty($template_fields['action_message'])) {
                $body_template .= '<br><br>' . __('Message from trusted servant','bmlt-workflow') . ':<br><br>{field:action_message}';
            }
        }
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send rejection email
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_rejection_email($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_rejection_email_subject');
        $body_template = get_option('bmltwf_rejection_email_template');
        $default_subject = __('NA Meeting Change Request Rejection - Submission ID','bmlt-workflow') . ' ' . $template_fields['change_id'];
        
        // If no custom template, use default body with action message
        if (empty($body_template)) {
            $body_template = __('Your meeting change has been rejected - change ID','bmlt-workflow') . ' ({field:change_id})';
            if (!empty($template_fields['action_message'])) {
                $body_template .= '<br><br>' . __('Message from trusted servant','bmlt-workflow') . ':<br><br>{field:action_message}';
            }
        }
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send FSO email
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_fso_email($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_fso_email_subject');
        $body_template = get_option('bmltwf_fso_email_template');
        $default_subject = __('New Meeting Starter Kit Request','bmlt-workflow');
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send correspondence notification to submitter
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_correspondence_submitter_notification($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_correspondence_submitter_email_subject');
        $body_template = get_option('bmltwf_correspondence_submitter_email_template');
        $default_subject = __('New correspondence about your meeting submission', 'bmlt-workflow');
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Send correspondence notification to admin
     *
     * @param string $to_address
     * @param array $context Context data
     * @param array $additional_fields Additional template fields
     * @return bool
     */
    public function send_correspondence_admin_notification($to_address, $context, $additional_fields = array(), $meeting_data = null)
    {
        $template_fields = array_merge($this->build_common_fields($context, $meeting_data), $additional_fields);
        
        $subject_template = get_option('bmltwf_correspondence_admin_email_subject');
        $body_template = get_option('bmltwf_correspondence_admin_email_template');
        $default_subject = __('New correspondence received - Submission ID', 'bmlt-workflow') . ' #' . $template_fields['change_id'];
        
        return $this->send_templated_email($to_address, $subject_template, $body_template, $template_fields, $default_subject);
    }

    /**
     * Substitute template fields in a string
     *
     * @param string $template
     * @param array $fields
     * @return string
     */
    private function substitute_template_fields($template, $fields)
    {
        foreach ($fields as $field => $value) {
            $placeholder = '{field:' . $field . '}';
            // Convert non-string values to strings
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = '';
            } else {
                $value = (string) $value;
            }
            $template = str_replace($placeholder, $value, $template);
        }
        
        return $template;
    }
}
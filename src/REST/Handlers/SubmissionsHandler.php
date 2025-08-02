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
use bmltwf\BMLTWF_Database;


class SubmissionsHandler
{
    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\REST\HandlerCore;

    protected $formats;
    protected $BMLTWF_Database;
    protected $bmlt_integration;

    public function __construct($intstub = null)
    {
        $this->initTableNames();
        
        if (empty($intstub)) {
            // $this->debug_log("SubmissionsHandler: Creating new Integration");
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }
        
        // $this->debug_log("SubmissionsHandler: Creating new BMLTWF_Database");        
        $this->BMLTWF_Database = new BMLTWF_Database();
    
    }

    public function get_submissions_handler($request)
    {
        global $wpdb;

        // Get pagination parameters
        $first = intval($request->get_param('first') ?? 0);
        $last = intval($request->get_param('last') ?? 20);
        $total = intval($request->get_param('total') ?? 0);
        $filter = $request->get_param('filter') ?? 'all';
        $search = $request->get_param('search') ?? '';
        
        // Calculate LIMIT and OFFSET
        $limit = $last - $first + 1;
        $offset = $first;

        // Build WHERE clause based on filter
        $where_clause = '';
        $where_params = [];
        
        switch ($filter) {
            case 'pending':
                $where_clause = ' WHERE change_made IS NULL ';
                break;
            case 'approved':
                $where_clause = ' WHERE change_made = "approved" ';
                break;
            case 'rejected':
                $where_clause = ' WHERE change_made = "rejected" ';
                break;
            case 'correspondence':
                $where_clause = ' WHERE (change_made = "correspondence_sent" OR change_made = "correspondence_received") ';
                break;
            default: // 'all'
                $where_clause = '';
                break;
        }
        
        // Add search functionality
        if (!empty($search)) {
            $search_clause = ' (submitter_name LIKE %s OR submitter_email LIKE %s OR change_id LIKE %s) ';
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            
            if (empty($where_clause)) {
                $where_clause = ' WHERE ' . $search_clause;
            } else {
                $where_clause .= ' AND ' . $search_clause;
            }
            $where_params = array_merge($where_params, [$search_param, $search_param, $search_param]);
        }
        
        // only show submissions we have access to
        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        if(current_user_can('manage_options'))
        {
            $sql = 'SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . $where_clause . ' ORDER BY change_id DESC LIMIT %d OFFSET %d';
            $sql = $wpdb->prepare($sql, array_merge($where_params, [$limit, $offset]));
        }
        else
        {
            $access_where = ' WHERE a.wp_uid = %d ';
            $access_params = [$current_uid];
            
            if (!empty($where_clause)) {
                $access_where .= str_replace('WHERE', 'AND', $where_clause);
                $access_params = array_merge($access_params, $where_params);
            }
            
            $sql = 'SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' s inner join ' . 
                  $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' a on s.serviceBodyId = a.serviceBodyId' . 
                  $access_where . ' ORDER BY s.change_id DESC LIMIT %d OFFSET %d';
            $sql = $wpdb->prepare($sql, array_merge($access_params, [$limit, $offset]));
        }
        
        // Count total records with the same filter
        if(current_user_can('manage_options'))
        {
            $total_sql = 'SELECT COUNT(*) FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . $where_clause;
            if (!empty($where_params)) {
                $total_sql = $wpdb->prepare($total_sql, $where_params);
            } else {
                $total_sql = $wpdb->prepare($total_sql);
            }
        }
        else
        {
            $access_where = ' WHERE a.wp_uid = %d ';
            $access_params = [$current_uid];
            if (!empty($where_clause)) {
                $access_where .= str_replace('WHERE', 'AND', $where_clause);
                $access_params = array_merge($access_params, $where_params);
            }
            
            $total_sql = 'SELECT COUNT(*) FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' s inner join ' . 
                        $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' a on s.serviceBodyId = a.serviceBodyId' . 
                        $access_where;
            $total_sql = $wpdb->prepare($total_sql, $access_params);
        }
        $total_count = $wpdb->get_var($total_sql);

        // $this->debug_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        if ($wpdb->last_error) {
            return new \WP_Error('bmltwf', 'Database error: ' . $wpdb->last_error);
        }
        $this->debug_log("result:");
        $this->debug_log(($result));
        foreach ($result as $key => $value) {
            $this->debug_log("changes requested:");
            $this->debug_log(json_decode($result[$key]['changes_requested'], true,3));
            $result[$key]['changes_requested'] = json_decode($result[$key]['changes_requested'], true, 3);
            $this->debug_log("id:");
            $this->debug_log($result[$key]['id']);

            $meeting_data = $this->bmlt_integration->getMeeting($result[$key]['id']);
            $this->debug_log("meeting data:");
            $this->debug_log($meeting_data);

            if (is_wp_error($meeting_data)) {
                $this->debug_log("wp error");
                $result[$key]['bmlt_meeting_data'] = null;
            } else {
                $result[$key]['bmlt_meeting_data'] = $meeting_data;
            }
            
            // Get correspondence thread_id if it exists
            $thread_sql = $wpdb->prepare(
                'SELECT thread_id FROM ' . $this->BMLTWF_Database->bmltwf_correspondence_table_name . ' WHERE change_id = %d LIMIT 1',
                $result[$key]['change_id']
            );
            $thread_id = $wpdb->get_var($thread_sql);
            $result[$key]['thread_id'] = $thread_id;
        }
        return array(
            'data' => $result,
            'recordsTotal' => intval($total_count),
            'recordsFiltered' => intval($total_count)
        );
    }

    public function delete_submission_handler($request)
    {

        global $wpdb;

        $sql = $wpdb->prepare('DELETE FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' where change_id="%d" limit 1', $request['change_id']);
        $result = $wpdb->query($sql);
        
        if ($wpdb->last_error) {
            return $this->bmltwf_rest_error('Database error: ' . $wpdb->last_error, 500);
        }
        
        if ($result === 0) {
            return $this->bmltwf_rest_error(__('Submission not found','bmlt-workflow'), 404);
        }

        return $this->bmltwf_rest_success(__('Deleted submission id ','bmlt-workflow') . $request['change_id']);
    }

    public function get_submission_handler($request)
    {
        global $wpdb;

        $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' where change_id="%d" limit 1', $request['change_id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    private function get_submission_id_with_permission_check($change_id)
    {
        $result = $this->get_submission_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }
        // Convert to array format for backward compatibility
        return (array) $result;
    }

    public function reject_submission_handler($request)
    {
        global $wpdb;


        $change_id = $request->get_param('change_id');

        $this->debug_log("rejection request for id " . $change_id);

        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        $submitter_email = $result['submitter_email'];
        $change_made = $result['change_made'];

        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->bmltwf_rest_error(__('Approve/reject already performed for submission id','bmlt-workflow').' '. $change_id, 422);
        }

        $params = $request->get_json_params();
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->bmltwf_rest_error(__('Reject message must be less than 1024 characters','bmlt-workflow'), 422);
            }
        } else {
            $this->debug_log("action message is null");
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where change_id="%d" limit 1',
            'rejected',
            $username,
            current_time('mysql', true),
            $message,
            $request['change_id']
        );

        $result = $wpdb->get_results($sql, ARRAY_A);

        //
        // send action email
        //

        $from_address = get_option('bmltwf_email_from_address');
        $to_address = $submitter_email;
        $subject = __('NA Meeting Change Request Rejection - Submission ID','bmlt-workflow')." " . $request['id'];
        $body = __('Your meeting change has been rejected - change ID','bmlt-workflow')." (" . $request['id'] . ")";

        if (!empty($message)) {
            $body .= '<br><br>'.__('Message from trusted servant','bmlt-workflow').':<br><br>' . $message;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->debug_log("Rejection email");
        $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body);
        wp_mail($to_address, $subject, $body, $headers);

        return $this->bmltwf_rest_success(__('Rejected submission id','bmlt-workflow').' ' . $change_id);
    }

    public function patch_submission_handler($request)
    {
        global $wpdb;


        $change_id = $request->get_param('change_id');

        $this->debug_log("patch request for id " . $change_id);

        // permitted change list from quickedit - notably no meeting id or service body
        $quickedit_change = $request->get_param('changes_requested');

        $change_subfields = array(
            "name",
            "startTime",
            "duration",
            "location_text",
            "location_street",
            "location_info",
            "location_municipality",
            "location_province",
            "location_postal_code_1",
            "day",
            "formatIds",
            "location_sub_province",
            "location_nation",
            "virtual_meeting_additional_info",
            "phone_meeting_number",
            "virtual_meeting_link",
            "venueType",
            "published",
            "virtualna_published",
            "latitude",
            "longitude"
        );

        foreach ($quickedit_change as $key => $value) {
            // $this->debug_log("checking " . $key);
            if (!in_array($key, $change_subfields)) {
                // $this->debug_log("removing " . $key);
                unset($quickedit_change[$key]);
            } elseif (is_array($value) && $key !== 'formatIds') {
                // Keep formatIds as array, remove other arrays
                unset($quickedit_change[$key]);
            }
            
            // Special handling for formatIds - ensure it's always an array of integers
            if ($key === 'formatIds') {
                if (is_string($value)) {
                    // Convert comma-separated string to array of integers
                    $quickedit_change[$key] = array_map('intval', explode(',', $value));
                } elseif (is_array($value)) {
                    // Ensure all values are integers
                    $quickedit_change[$key] = array_map('intval', $value);
                }
            }
            
            // Ensure time fields are in HH:MM format (not HH:MM:SS)
            if ($key === 'startTime' || $key === 'duration') {
                if (is_string($value) && preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $value, $matches)) {
                    // Convert HH:MM:SS to HH:MM
                    $quickedit_change[$key] = $matches[1] . ':' . $matches[2];
                } elseif (is_string($value) && strlen($value) > 5) {
                    // Truncate to first 5 characters (HH:MM)
                    $quickedit_change[$key] = substr($value, 0, 5);
                }
            }
        }

        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        $change_made = $result['change_made'];

        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->bmltwf_rest_error(__('Approve/reject already performed for submission id','bmlt-workflow').' '. $change_id, 422);
        }

        // $this->debug_log("change made is ".$change_made);

        // get our saved changes from the db
        $saved_change = json_decode($result['changes_requested'], 1);

        // put the quickedit ones over the top

        // $this->debug_log("merge before - saved");
        // $this->debug_log(($saved_change));
        // $this->debug_log("merge before - quickedit");
        // $this->debug_log(($quickedit_change));

        $merged_change = array_merge($saved_change, $quickedit_change);

        // $this->debug_log("merge after - saved");
        // $this->debug_log(($merged_change));

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set changes_requested = "%s",change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where change_id="%d" limit 1',
            json_encode($merged_change),
            'updated',
            $username,
            current_time('mysql', true),
            NULL,
            $request['change_id']
        );
        // $this->debug_log(($sql));

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $this->bmltwf_rest_success(__('Updated submission id','bmlt-workflow').' ' . $change_id);
    }

    private function do_geolocate($change)
    {
        // workaround for server side geolocation

        $locfields = array("location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation");
        $locdata = array();
        foreach ($locfields as $field) {
            if (!empty($change[$field])) {
                $locdata[] = $change[$field];
            }
        }
        $locstring = implode(', ', $locdata);
        $this->debug_log("GMAPS location lookup = " . $locstring);

        $location = $this->bmlt_integration->geolocateAddress($locstring);
        if (is_wp_error($location)) {
            return $location;
        }

        $latlng = array();
        $latlng['latitude'] = $location['results'][0]['geometry']['location']['lat'];
        $latlng['longitude'] = $location['results'][0]['geometry']['location']['lng'];
        $this->debug_log("GMAPS location lookup returns = " .  $latlng['latitude']  . " " . $latlng['longitude']);

        return $latlng;
    }

    public function approve_submission_handler($request)
    {
        global $wpdb;

        // body parameters
        $params = $request->get_json_params();
        // url parameters from parsed route
        $change_id = $request->get_param('change_id');

        // clean/validate supplied approval message
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->bmltwf_rest_error(__('Approve message must be less than 1024 characters','bmlt-workflow'), 422);
            }
        }

        // retrieve our submission id from the one specified in the route
        $this->debug_log("getting changes for id " . $change_id);
        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }
        $this->debug_log("submission");
        $this->debug_log($result);

        // can't approve an already actioned submission
        $change_made = $result['change_made'];
        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->bmltwf_rest_error(__('Approve/reject already performed for submission id','bmlt-workflow').' '. $change_id, 422);
        }

        $change = json_decode($result['changes_requested'], 1);

        // handle request to add email
        $submitter_email = $result['submitter_email'];
        $submitter_name = $result['submitter_name'];
        $submitter_phone = $change['contact_number']??'';

        // handle fso request
        // $this->debug_log("starter kit");
        // $this->debug_log($change['starter_kit_required']);
        // $this->debug_log($change);
        // $this->debug_log($change['starter_kit_postal_address']);
        if ((!empty($change['starter_kit_required'])) && ($change['starter_kit_required'] === 'yes') && (!empty($change['starter_kit_postal_address']))) {
            $this->debug_log("starter kit requested");
            $starter_kit_required = true;
            $starter_kit_postal_address = $change['starter_kit_postal_address'];
            $starter_kit_contact_number = $change['contact_number'] ?? 'No phone number provided';
        }
        else
        {
            $this->debug_log("starter kit not requested");
            $starter_kit_required = false;
        }

        $add_contact = false;
        if ((!empty($change['add_contact'])) && ($change['add_contact'] === 'yes')) {
            $add_contact = true;
        }

        // strip out anything that somehow made it this far, before we send it to bmlt
        $change_subfields = array(
            "name",
            "id",
            "startTime",
            "duration",
            "location_text",
            "location_street",
            "location_info",
            "location_municipality",
            "location_province",
            "location_postal_code_1",
            "day",
            "serviceBodyId",
            "formatIds",
            "location_sub_province",
            "location_nation",
            "virtual_meeting_additional_info",
            "phone_meeting_number",
            "virtual_meeting_link",
            "venueType",
            "published",
            "virtualna_published",
            "latitude",
            "longitude"
        );

        foreach ($change as $key => $value) {
            if (!in_array($key, $change_subfields)) {
                unset($change[$key]);
            }
        }

        if ($add_contact === true) {
            $change['contact_email_1'] = $submitter_email;
            $change['contact_name_1'] = $submitter_name;
            $change['contact_phone_1'] = $submitter_phone;
        }

        // approve based on different change types
        $submission_type = $result['submission_type'];
        $this->debug_log("change type = " . $submission_type);
        switch ($submission_type) {
            case 'reason_new':
                if ((!array_key_exists('latitude',$change))&&(!array_key_exists('longitude',$change)))
                {
                    if ($this->bmlt_integration->isAutoGeocodingEnabled('auto')) {
                        // run our geolocator on the address
                        $latlng = $this->do_geolocate($change);
                        if (is_wp_error($latlng)) {
                            return $latlng;
                        }
                        $change['latitude'] = $latlng['latitude'];
                        $change['longitude'] = $latlng['longitude'];
                    } else {
                        $latlng = $this->bmlt_integration->getDefaultLatLong();
                        $change['latitude'] = $latlng['latitude'];
                        $change['longitude'] = $latlng['longitude'];
                    }
                }
                $change['published'] = true;

                // If we have a virtualna_published setting, modify worldid appropriately
                if(array_key_exists('virtualna_published', $change))
                {
                    $this->debug_log("virtualna_published = ".$change['virtualna_published']);
                    $change["worldid_mixed"] = $this->worldid_publish_to_virtualna(($change["virtualna_published"]=== 1)?true:false, "");
                    $this->debug_log("new worldid = ".$change["worldid_mixed"]);
                    unset($change["virtualna_published"]);
                }


                $response = $this->bmlt_integration->createMeeting($change);
                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $this->bmltwf_rest_error(__('Error creating meeting','bmlt-workflow') . ': ' . $error_message, 422);
                }

                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $this->bmltwf_rest_error(__('Error creating meeting','bmlt-workflow') . ': ' . $error_message, 422);
                }

                break;
            case 'reason_change':
                $change['id'] = $result['id'];

                $this->debug_log("CHANGE");
                $this->debug_log(($change));

                // geolocate based on changes - apply the changes to the BMLT version, then geolocate
                $bmlt_meeting = $this->bmlt_integration->getMeeting($result['id']);
                if (is_wp_error($bmlt_meeting)) {
                    return $this->bmltwf_rest_error(__('Error retrieving meeting details','bmlt-workflow'), 422);
                }

                $locfields = array("location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation");

                foreach ($locfields as $field) {
                    if (!empty($change[$field])) {
                        $bmlt_meeting[$field] = $change[$field];
                    }
                }

                if ((!array_key_exists('latitude',$change))&&(!array_key_exists('longitude',$change)))
                {
                    if ($this->bmlt_integration->isAutoGeocodingEnabled('auto')) {
                        $this->debug_log("auto geocoding enabled, performing geolocate");
                        $latlng = $this->do_geolocate($bmlt_meeting);
                        if (is_wp_error($latlng)) {
                            return $latlng;
                        }

                        // add the new geo to the original change
                        $change['latitude'] = $latlng['latitude'];
                        $change['longitude'] = $latlng['longitude'];
                    } else {

                        $latexists = false;
                        $longexists = false;

                        if ((array_key_exists('latitude',$bmlt_meeting)) && $bmlt_meeting['latitude'] != 0)
                        {
                            $this->debug_log("latitude found");
                            $latexists = true;
                        }
                        else
                        {
                            $this->debug_log("latitude not found");
                        }
                        if ((array_key_exists('longitude',$bmlt_meeting)) && $bmlt_meeting['longitude'] != 0)
                        {
                            $this->debug_log("longitude found");
                            $longexists = true;
                        }
                        else
                        {
                            $this->debug_log("longitude not found");
                        }

                        // update this only if we have no meeting lat/long already set
                        if (!$latexists && !$longexists) {
                            $this->debug_log("blank lat/long - updating to defaults");
        
                            $latlng = $this->bmlt_integration->getDefaultLatLong();
                            $change['latitude'] = $latlng['latitude'];
                            $change['longitude'] = $latlng['longitude'];
                        }
                    }
                }
                $bmlt_venueType = $bmlt_meeting['venueType'];
                // $this->debug_log("bmlt_meeting[venueType]=");
                // $this->debug_log($bmlt_venueType);

                $change_venueType = $change['venueType'] ?? '0';
                // $this->debug_log("change[venueType]=");
                // $this->debug_log($change_venueType);

                $is_change_to_f2f = (($change_venueType == 1)&&($bmlt_venueType != $change_venueType));
                // $this->debug_log("is_change_to_f2f=");
                // $this->debug_log($is_change_to_f2f);

                // if bmltwf_remove_virtual_meeting_details_on_venue_change is true, then explicitly blank out our virtual meeting settings when the venue
                // is changed to face to face

                if ((get_option('bmltwf_remove_virtual_meeting_details_on_venue_change') == 'true') && $is_change_to_f2f)
                {
                    $change["virtual_meeting_additional_info"]="";
                    $change["phone_meeting_number"]="";
                    $change["virtual_meeting_link"]="";
                }

                // If we have a virtualna_published setting, modify worldid appropriately
                if(array_key_exists('virtualna_published', $change) && array_key_exists('worldid_mixed',$bmlt_meeting))
                {
                    $this->debug_log("virtualna_published = ".$change['virtualna_published']);
                    $change["worldid_mixed"] = $this->worldid_publish_to_virtualna(($change["virtualna_published"]=== 1)?true:false, $bmlt_meeting['worldid_mixed']);
                    $this->debug_log("new worldid = ".$change["worldid_mixed"]);
                    unset($change["virtualna_published"]);
                }

                $response = $this->bmlt_integration->updateMeeting($change);

                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return $this->bmltwf_rest_error(__('Error updating meeting','bmlt-workflow') . ': ' . $error_message, 422);
                }

                break;
            case 'reason_close':

                $this->debug_log(($params));

                // are we doing a delete or an unpublish on close?
                if ((!empty($params['delete'])) && ($params['delete'] == "true")) {

                    $response = $this->bmlt_integration->deleteMeeting($result['id']);

                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();
                        return $this->bmltwf_rest_error(__('Error deleting meeting','bmlt-workflow') . ': ' . $error_message, 422);
                    }
                } else {
                    // unpublish by default
                    $change['published'] = false;
                    $change['id'] = $result['id'];
                    $response = $this->bmlt_integration->updateMeeting($change);

                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();
                        return $this->bmltwf_rest_error(__('Error updating meeting','bmlt-workflow') . ': ' . $error_message, 422);
                    }

                }

                break;

            default:
                return $this->bmltwf_rest_error(__('This change type cannot be approved','bmlt-workflow')." ({$submission_type})", 422);
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where change_id="%d" limit 1',
            'approved',
            $username,
            current_time('mysql', true),
            $message,
            $request['change_id']
        );
        $this->debug_log("SQL");
        $this->debug_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        $this->debug_log("RESULT");
        $this->debug_log($result);

        $from_address = get_option('bmltwf_email_from_address');

        //
        // send action email
        //

        $to_address = $submitter_email;
        $subject = __('NA Meeting Change Request Approval - Submission ID','bmlt-workflow')." " . $request['change_id'];
        $body = __('Your meeting change has been approved - change ID','bmlt-workflow')." (" . $request['change_id'] . ")";
        if (!empty($message)) {
            $body .= '<br><br>'.__('Message from trusted servant','bmlt-workflow').':<br><br>' . $message;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->debug_log("Approval email");
        $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . print_r($headers, true));
        wp_mail($to_address, $subject, $body, $headers);

        // only do FSO features if option is enabled
        if (get_option('bmltwf_fso_feature') === 'display') {
            //
            // send FSO email
            //

            if ($submission_type == "reason_new") {
                $this->debug_log($change);
                if ($starter_kit_required) {
                    $template_fields=array('starter_kit_postal_address'=>$starter_kit_postal_address,
                    'submitter_name' => $submitter_name,
                    'name' => $change['name'],
                    'contact_number' => $starter_kit_contact_number);

                    $this->debug_log("We're sending a starter kit");
                    $template = get_option('bmltwf_fso_email_template');
                    if (!empty($template)) {
                        $subject = __('Starter Kit Request','bmlt-workflow');
                        $to_address = get_option('bmltwf_fso_email_address');
                        $fso_subfields = array('contact_number','submitter_name', 'name', 'starter_kit_postal_address');

                        foreach ($fso_subfields as $field) {
                            $subfield = '{field:' . $field . '}';
                            if (!empty($template_fields[$field])) {
                                $subwith = $template_fields[$field];
                            } else {
                                $subwith = '(blank)';
                            }
                            $template = str_replace($subfield, $subwith, $template);
                        }
                        $body = $template;
                        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
                        $this->debug_log("FSO email");
                        $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . print_r($headers, true));

                        wp_mail($to_address, $subject, $body, $headers);
                    } else {
                        $this->debug_log("FSO email is empty");
                    }
                }
            }
        }

        // wip for #89

        // if(get_option('bmltwf_service_body_contact_notify')==='true')
        // {
        //     /*
        //     * Send acknowledgement email to the service body email
        //     */


        //     $to_address = $submitter_email;
        //     $subject = "NA Meeting Change Request Acknowledgement - " . $this->submission_type_to_friendlyname($submission_type);

        //     $template = get_option('bmltwf_service_body_contact_email_template');

        //     $subfield = '{field:submission}';
        //     $subwith = $this->submission_format($change);
        //     $template = str_replace($subfield, $subwith, $template);

        //     $body = $template;

        //     $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        //     $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . print_r($headers,true));
        //     wp_mail($to_address, $subject, $body, $headers);
        // }


        return $this->bmltwf_rest_success(__('Approved submission id','bmlt-workflow').' ' . $change_id);
    }


    function worldid_publish_to_virtualna(bool $publish, string $worldid): string
    {

        $new_char = 'U';
        if($publish)
        {
            $new_char = 'G';
        }
        $new_worldid = "";
        if(is_numeric(substr($worldid, 0, 1)))
        {
            $new_worldid = $new_char . $worldid;
        }
        else
        {
            $new_worldid = $worldid;
            $new_worldid[0] = $new_char;
        }
        return $new_worldid;
    }

    private function submission_type_to_friendlyname($reason)
    {
        switch ($reason) {
            case "reason_new":
                $submission_type = __('New Meeting','bmlt-workflow');
                break;
            case "reason_close":
                $submission_type = __('Close Meeting','bmlt-workflow');
                break;
            case "reason_change":
                $submission_type = __('Modify Meeting','bmlt-workflow');
                break;
        }
        return $submission_type;
    }

    private function get_emails_by_servicebody_id($id)
    {
        global $wpdb;

        $emails = array();
        $sql = $wpdb->prepare('SELECT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' where serviceBodyId="%d"', $id);
        $result = $wpdb->get_col($sql);
        foreach ($result as $key => $value) {
            $user = get_user_by('ID', $value);
            $emails[] = $user->user_email;
        }
        return implode(',', $emails);
    }

    private function populate_formats()
    {
        if ($this->formats === null) {
            $this->formats = $this->bmlt_integration->getMeetingFormats();
        }
    }

    private function invalid_form_field($field)
    {
        return $this->bmltwf_rest_error(__('Form field','bmlt-workflow').' "' . $field . '" '.__('is invalid','bmlt-workflow').'.', 422);
    }



    public function meeting_update_form_handler_rest($request)
    {
        $data = $request->get_json_params();

        $reason_new_bool = false;
        $reason_change_bool = false;
        $reason_close_bool = false;
        $virtual_meeting_bool = false;

        
        // strip blanks
        foreach ($data as $key => $value) {
            $this->debug_log("stripping blank from ".$key);
            if (($data[$key] === "") || ($data[$key] === NULL)) {
                $this->debug_log("stripped");
                unset($data[$key]);
            }
        }

        if (isset($data['update_reason'])) {
            // we use these to enforce required parameters in the next section
            $reason_new_bool = ($data['update_reason'] === 'reason_new');
            $reason_change_bool = ($data['update_reason'] === 'reason_change');
            $reason_close_bool = ($data['update_reason'] === 'reason_close');
            // handle meeting formats
            $this->populate_formats();
            $venueType = $data['venueType'] ?? '0';
            
            $virtual_meeting_bool = (intval($venueType) !== 1);

            $require_postcode = false;
            if (get_option('bmltwf_optional_postcode') === 'displayrequired') {
                $require_postcode = true;
            }

            $require_nation = false;
            if (get_option('bmltwf_optional_location_nation') === 'displayrequired') {
                $require_nation = true;
            }

            $require_province = false;
            if (get_option('bmltwf_optional_location_province') === 'displayrequired') {
                $require_province = true;
            }

            $require_sub_province = false;
            if (get_option('bmltwf_optional_location_sub_province') === 'displayrequired') {
                $require_sub_province = true;
            }

            $require_meeting_formats = false;
            if (get_option('bmltwf_required_meeting_formats') === 'true') {
                $require_meeting_formats = true;
            }

            $fso_feature = false;
            if (get_option('bmltwf_fso_feature') === 'display') {
                $fso_feature = true;
            }
        }

        if (!(isset($data['update_reason']) || (!$reason_new_bool && !$reason_change_bool && !$reason_close_bool))) {
            return $this->bmltwf_rest_error('No valid meeting update reason provided', 422);
        }

        // sanitize any input
        // array value [0] is 'input type', [1] is boolean (true if required)

        $subfields = array(
            "update_reason" => array("text", true),
            "id" => array("number", $reason_change_bool | $reason_close_bool),
            "first_name" => array("text", true),
            "last_name" => array("text", true),
            "name" => array("text", $reason_new_bool),
            "startTime" => array("time", $reason_new_bool),
            "duration" => array("time", $reason_new_bool),
            "venueType" => array("venue", $reason_new_bool | $reason_change_bool),
            // location text and street only required if its not a virtual meeting #75
            "location_text" => array("text", $reason_new_bool && (!$virtual_meeting_bool)),
            "location_street" => array("text", $reason_new_bool && (!$virtual_meeting_bool)),
            "location_info" => array("text", false),
            "location_municipality" => array("text", $reason_new_bool),
            "day" => array("number", $reason_new_bool),
            "serviceBodyId" => array("number", $reason_new_bool),
            "email_address" => array("email", true),
            "contact_number" => array("text", false),
            // optional #93
            "formatIds" => array("intarray", $reason_new_bool && $require_meeting_formats),
            "additional_info" => array("textarea", $reason_close_bool),
            "starter_kit_postal_address" => array("textarea", false),
            "starter_kit_required" => array("text", $reason_new_bool && $fso_feature),
            "location_nation" => array("text", $reason_new_bool && $require_nation),
            "location_province" => array("text", $reason_new_bool && $require_province),
            "location_sub_province" => array("text", $reason_new_bool && $require_sub_province),
            // postcode can be a text format #78
            "location_postal_code_1" => array("text", $reason_new_bool && $require_postcode),
            "group_relationship" => array("text", true),
            "add_contact" => array("yesno", true),
            "virtual_meeting_additional_info" => array("text", false),
            "phone_meeting_number" => array("text", false),
            "virtual_meeting_link" => array("url", false),
            "published" => array("boolnum", $reason_change_bool),
            "virtualna_published" => array("boolnum", ($reason_change_bool||$reason_new_bool) && $virtual_meeting_bool)
        );

        $sanitised_fields = array();

        // blank meeting id if not provided
        $sanitised_fields['id'] = 0;
        
        // sanitise all provided fields and drop all others
        foreach ($subfields as $field => $validation) {
            $field_type = $validation[0];
            $field_is_required = $validation[1];
            // if the form field is required, check if the submission is empty or non existent
            if ($field_is_required && !array_key_exists($field,$data)) {
                return $this->bmltwf_rest_error(__('Form field','bmlt-workflow').' "' . $field . '" '.__('is required','bmlt-workflow').'.', 422);
            }

            // special handling for temporary virtual
            if ($field === "venueType" && $virtual_meeting_bool && ($data["update_reason"] !== 'reason_close'))
            {
                $phone_meeting_number_provided = $data["phone_meeting_number"]??false;
                // $this->debug_log("phone meeting number");
                // $this->debug_log($phone_meeting_number_provided);
                $virtual_meeting_link_provided = $data["virtual_meeting_link"]??false;
                // $this->debug_log(" meeting link");
                // $this->debug_log($virtual_meeting_link_provided);
                $virtual_meeting_additional_info_provided = $data["virtual_meeting_additional_info"]??false;
                // $this->debug_log(" meeting info");
                // $this->debug_log($virtual_meeting_additional_info_provided);

                if($virtual_meeting_bool)
                {
                    // need to provide either phone number or both the link/additional info
                    if(!$phone_meeting_number_provided)
                    {
                        if (!$virtual_meeting_link_provided || !$virtual_meeting_additional_info_provided)
                        {
                            return $this->bmltwf_rest_error(__('You must provide at least a phone number for a Virtual Meeting, or fill in both the Virtual Meeting link and Virtual Meeting additional information','bmlt-workflow').'.', 422);
                        }
                    }
                }
            }
            $this->debug_log("field = ".$field);

            if(isset($data[$field])){
                $this->debug_log("isset(data[field]) = true");
            }
            else
            {
                $this->debug_log("isset(data[field]) = false");
            }

            // sanitise only fields that have been provided
            if (isset($data[$field])) {
                switch ($field_type) {
                    case ('text'):
                        $data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case ('intarray'):
                        if (!is_array($data[$field])) {
                                return $this->invalid_form_field($field);
                            }
                            $clean_array = array();
                            foreach ($data[$field] as $item) {
                                if (!is_numeric($item)) {
                                    return $this->invalid_form_field($field);
                                }
                                $clean_array[] = intval($item);
                            }
                            $data[$field] = $clean_array;
                        break;
                    case ('yesno'):
                        if (($data[$field] !== 'yes') && ($data[$field] !== 'no')) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('boolnum'):
                        if(($data[$field]!= '0') && ($data[$field] != '1'))
                        {
                            return $this->invalid_form_field($field);
                        }
                        else
                        {
                            $data[$field] = intval($data[$field]);
                        }
                        break;
                    case ('number'):
                    case ('bigint'):
                        $data[$field] = intval($data[$field]);
                        break;
                    case ('venue'):
                        $data[$field] = intval($data[$field]);
                        if(($data[$field])<1 || $data[$field]>4)
                        {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('day'):
                        if (!(($data[$field] >= 0) && ($data[$field] <= 6))) {
                            return $this->invalid_form_field($field);
                        }
                        $data[$field] = intval($data[$field]);
                        break;
                    case ('url'):
                        $data[$field] = esc_url_raw($data[$field], array('http', 'https'));
                        break;
                    case ('email'):
                        $data[$field] = sanitize_email($data[$field]);
                        if (empty($data[$field])) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('textarea'):
                        $data[$field] = sanitize_textarea_field($data[$field]);
                        if (strlen($data[$field]) > 512) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('time'):
                        if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:00$/', $data[$field])) {
                            return $this->invalid_form_field($field);
                        }
                        $data[$field] = substr($data[$field], 0, 5);
                        break;
                    default:
                        return $this->bmltwf_rest_error(__('Form processing error','bmlt-workflow'), 500);
                        break;
                }
                $sanitised_fields[$field] = $data[$field];
            }
        }

        // drop out everything that isnt in our approved list
        $data = array();

        // fields used throughout the rest of the form processing
        $reason = $sanitised_fields['update_reason'];

        // ensure service body is correctly set

        if (empty($sanitised_fields['serviceBodyId'])) {
            // we should never have a blank service body unless it is 'other' request
            return $this->bmltwf_rest_error('Form field "serviceBodyId" is required.', 422);
        }

        // main switch for meeting change type
        //
        // this is where we create our submission for the database changes_requested field

        $submission = array();

        switch ($reason) {
            case ('reason_new'):
                // $subject = 'New meeting notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "name",
                    "startTime",
                    "duration",
                    "location_text",
                    "location_street",
                    "location_info",
                    "location_municipality",
                    "location_province",
                    "location_postal_code_1",
                    "location_nation",
                    "location_sub_province",
                    "day",
                    "serviceBodyId",
                    "formatIds",
                    "contact_number",
                    "group_relationship",
                    "add_contact",
                    "additional_info",
                    "virtual_meeting_additional_info",
                    "phone_meeting_number",
                    "virtual_meeting_link",
                    "starter_kit_required",
                    "starter_kit_postal_address",
                    "virtualna_published",
                    "venueType"
                );

                // new meeting - add all fields to the changes requested
                foreach ($allowed_fields as $field) {
                    // make sure its not a null entry, ie not entered on the frontend form
                    // Special handling for day field which can be 0
                    if (!empty($sanitised_fields[$field]) || (isset($sanitised_fields[$field]) && ($sanitised_fields[$field] === 0 || $sanitised_fields[$field] === '0'))) {
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }
                // always mark our submission as published for a new meeting
                $submission['published'] = true;

                break;
            case ('reason_change'):

                // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "name",
                    "startTime",
                    "duration",
                    "location_text",
                    "location_street",
                    "location_info",
                    "location_municipality",
                    "location_province",
                    "location_postal_code_1",
                    "location_nation",
                    "location_sub_province",
                    "day",
                    "serviceBodyId",
                    "formatIds",
                    "virtual_meeting_additional_info",
                    "phone_meeting_number",
                    "virtual_meeting_link",
                    "venueType",
                    "published",
                );

                $allowed_fields_extra = array(
                    "contact_number",
                    "group_relationship",
                    "add_contact",
                    "additional_info",
                    "virtualna_published",
                );

                $bmlt_meeting = $this->bmlt_integration->getMeeting($sanitised_fields['id']);
                $this->debug_log("Getting info for meeting ". $sanitised_fields['id']);
                $this->debug_log(($bmlt_meeting));
                if (\is_wp_error($bmlt_meeting)) {
                    return $this->bmltwf_rest_error(__('Error retrieving meeting details','bmlt-workflow'), 422);
                }
                // strip blanks from BMLT
                foreach ($bmlt_meeting as $key => $value) {
                    if (($bmlt_meeting[$key] === "") || ($bmlt_meeting[$key] === NULL)) {
                        unset($bmlt_meeting[$key]);
                    }
                }

                $submission_count = 0;

                $this->debug_log("Sanitised fields");
                $this->debug_log($sanitised_fields);

                // Compare submitted fields with BMLT data and track changes
                foreach ($allowed_fields as $field) {
                    $bmlt_field = $bmlt_meeting[$field] ?? false;
                    $this->debug_log("Checking " . $field);
                    
                    // Check if field exists in submitted data
                    $field_submitted = array_key_exists($field, $sanitised_fields);
                    $submitted_value = $field_submitted ? $sanitised_fields[$field] : null;
                    
                    // Case 1: Field doesn't exist in BMLT but was submitted with a value
                    if (($bmlt_field === false || $bmlt_field === null || $bmlt_field === '') && 
                        $field_submitted && !empty($submitted_value)) {
                        // $this->debug_log("Case 1 adding " . $field);

                        $submission[$field] = $submitted_value;
                        $submission_count++;
                    }
                    // Case 2: Field exists in BMLT and was submitted with a different value
                    elseif ($bmlt_field !== false && $field_submitted && $bmlt_field != $submitted_value) {
                        // Don't allow service body changes
                        if ($field === 'serviceBodyId') {
                            return $this->bmltwf_rest_error(__('Service body cannot be changed.','bmlt-workflow'), 403);
                        }
                        $submission[$field] = $submitted_value;
                        $submission_count++;
                    }
                    // Case 3: Field exists in BMLT but was not submitted (implying deletion)
                    elseif ($bmlt_field !== false && !$field_submitted) {
                        // Don't allow service body changes
                        if ($field === 'serviceBodyId') {
                            return $this->bmltwf_rest_error(__('Service body cannot be changed.','bmlt-workflow'), 403);
                        }
                        // $this->debug_log("Case 3 adding " . $field);
                        $submission[$field] = "";
                        $submission_count++;
                    }
                    
                    // Store original value for reference
                    if ($bmlt_field !== false && $bmlt_field !== null && ($bmlt_field !== '' || is_numeric($bmlt_field))) {
                        $submission["original_" . $field] = $bmlt_field;
                    }
                }

                if (!$submission_count) {
                    return $this->bmltwf_rest_error(__('Nothing was changed.','bmlt-workflow'), 422);
                }
                $this->debug_log("submission");
                $this->debug_log($submission);

                // add in extra form fields (non BMLT fields) to the submission
                foreach ($allowed_fields_extra as $field) {
                    if (array_key_exists($field, $sanitised_fields)) {
                        // Only include virtualna_published if the meeting is virtual
                        if ($field === 'virtualna_published' && !$virtual_meeting_bool) {
                            continue;
                        }
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }

                $this->debug_log("SUBMISSION");
                $this->debug_log(($submission));
                $this->debug_log("BMLT MEETING");
                $this->debug_log(($bmlt_meeting));

                break;
            case ('reason_close'):
                // $subject = 'Close meeting notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "contact_number",
                    "group_relationship",
                    "add_contact",
                    "serviceBodyId",
                    "additional_info",
                );

                foreach ($allowed_fields as $item) {
                    if (isset($sanitised_fields[$item])) {
                        $submission[$item] = $sanitised_fields[$item];
                    }
                }

                // populate the meeting name/time/day so we dont need to do it again on the submission page
                $bmlt_meeting = $this->bmlt_integration->getMeeting($sanitised_fields['id']);
                // change our meeting return to v2 format so we can handle original components
                // $bmlt_meeting = $this->bmlt_integration->convertv3meetingtov2($bmlt_meeting);

                if(\is_wp_error($bmlt_meeting))
                {
                    return $this->bmltwf_rest_error(__('Error retrieving meeting details','bmlt-workflow'), 422);
                }
                $this->debug_log("BMLT MEETING");
                $this->debug_log(($bmlt_meeting));

                $submission['name'] = $bmlt_meeting['name'];
                $submission['day'] = $bmlt_meeting['day'];
                $submission['startTime'] = $bmlt_meeting['startTime'];

                break;

            default:
                return $this->bmltwf_rest_error(__('Invalid meeting change','bmlt-workflow'), 422);
        }

        $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
        $submitter_email = $sanitised_fields['email_address'];

        // max size check for #7
        $chg = wp_json_encode($submission, 0, 2);

        if (strlen($chg) >= 2048) {
            return $this->bmltwf_rest_error(__('Meeting change request exceeds maximum size','bmlt-workflow'), 422);
        }
        // insert into submissions db
        global $wpdb;

        $wpdb->insert(
            $this->BMLTWF_Database->bmltwf_submissions_table_name,
            array(
                'submission_time'   => current_time('mysql', true),
                'id' => $sanitised_fields['id'],
                'submitter_name' => $submitter_name,
                'submission_type'  => $reason,
                'submitter_email' => $submitter_email,
                'changes_requested' => $chg,
                'serviceBodyId' => $sanitised_fields['serviceBodyId']
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
        $log_submission = json_encode(array(
            'submission_time'   => current_time('mysql', true),
            'id' => $sanitised_fields['id'],
            'submitter_name' => $submitter_name,
            'submission_type'  => $reason,
            'submitter_email' => $submitter_email,
            'changes_requested' => $chg,
            'serviceBodyId' => $sanitised_fields['serviceBodyId']
        ));


        $insert_id = $wpdb->insert_id;
        // last resort capture of the submission in our logs
        error_log("[bmltwf - submission log id = " . $insert_id . "]");
        error_log($log_submission);
        
        $message = array(
            "message" => __('Form submission successful, submission id','bmlt-workflow').' ' . $insert_id,
            "form_html" => '<h3 id="bmltwf_response_message">'.__('Form submission successful, your submission id is','bmlt-workflow').' #' . $insert_id . '. '.__('You will also receive an email confirmation of your submission.','bmlt-workflow').'</h3>'
        );

        // Send our emails out

        // Common email fields
        $from_address = get_option('bmltwf_email_from_address');

        /*
        * Send a notification to the configured trusted servants for the correct service body
        */

        $submission_type = $this->submission_type_to_friendlyname($reason);

        $sblist = $this->bmlt_integration->getServiceBodies();
        if(\is_wp_error(($sblist)))
        {
            return $this->bmltwf_rest_error(__('Error retrieving service bodies'), 422);
        }
        $this->debug_log("retrieved sblist ");
        $this->debug_log($sblist);

        $to_address = $this->get_emails_by_servicebody_id($sanitised_fields['serviceBodyId']);
        $subject = '[bmlt-workflow] ' . $submission_type . ' '.__('request received','bmlt-workflow').' - ' . $sblist[$sanitised_fields['serviceBodyId']]['name'] . ' - '.__('Change ID','bmlt_workflow').' #' . $insert_id;
        
        // Use admin notification template
        $template = get_option('bmltwf_admin_notification_email_template');
        $template_fields = array(
            'change_id' => $insert_id,
            'submitter_name' => $submitter_name,
            'submitter_email' => $submitter_email,
            'submission_type' => $submission_type,
            'service_body_name' => $sblist[$sanitised_fields['serviceBodyId']]['name'],
            'submission_time' => current_time('mysql', true),
            'submission' => $this->submission_format($submission),
            'admin_url' => get_site_url() . '/wp-admin/admin.php?page=bmltwf-submissions',
            'site_name' => get_bloginfo('name')
        );
        
        $body = $template;
        foreach ($template_fields as $field => $value) {
            $subfield = '{field:' . $field . '}';
            $body = str_replace($subfield, $value, $body);
        }
        
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . print_r($headers, true));
        wp_mail($to_address, $subject, $body, $headers);

        /*
        * Send acknowledgement email to the submitter
        */


        $to_address = $submitter_email;
        $subject = __('NA Meeting Change Request Acknowledgement - Submission ID','bmlt-workflow').' ' . $insert_id;

        $template = get_option('bmltwf_submitter_email_template');

        $subfield = '{field:submission}';
        $subwith = $this->submission_format($submission);
        $template = str_replace($subfield, $subwith, $template);

        $body = $template;

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . print_r($headers, true));
        wp_mail($to_address, $subject, $body, $headers);

        return $this->bmltwf_rest_success($message);
    }

    private function submission_format($submission)
    {

        $table = '';
        $this->populate_formats();
        foreach ($submission as $key => $value) {
            if (!is_string($value) || (is_string($value) && !empty($value))) {
            switch ($key) {
                case "name":
                    $table .= '<tr><td>'.__('Meeting Name','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "startTime":
                    $table .= '<tr><td>'.__('Start Time','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "duration":
                    $table .= '<tr><td>'.__('Duration','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_text":
                    $table .= '<tr><td>'.__('Location','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_street":
                    $table .= '<tr><td>'.__('Street','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_info":
                    $table .= '<tr><td>'.__('Location Info','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_municipality":
                    $table .= '<tr><td>'.__('Municipality','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_province":
                    $table .= '<tr><td>'.__('Province/State','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_sub_province":
                    $table .= '<tr><td>'.__('SubProvince','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_nation":
                    $table .= '<tr><td>'.__('Nation','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "location_postal_code_1":
                    $table .= '<tr><td>'.__('PostCode','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "group_relationship":
                    $table .= '<tr><td>'.__('Relationship to Group','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "day":
                    $weekdays = [__('Error'), __('Sunday','bmlt-workflow'), __('Monday','bmlt-workflow'), __('Tuesday','bmlt-workflow'), __('Wednesday','bmlt-workflow'), __('Thursday','bmlt-workflow'), __('Friday','bmlt-workflow'), __('Saturday','bmlt-workflow')];
                    $table .= '<tr><td>'.__('Meeting Day','bmlt-workflow').':</td><td>' . $weekdays[$value] . '</td></tr>';
                    break;
                case "additional_info":
                    $table .= '<tr><td>'.__('Additional Info','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "other_reason":
                    $table .= '<tr><td>'.__('Other Reason','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "contact_number":
                    $table .= "<tr><td>'.__('Contact number (confidential)','bmlt-workflow').':</td><td>" . $value . '</td></tr>';
                    break;
                case "add_contact":
                    $result = ($value === 'yes' ? 'Yes' : 'No');
                    $table .= '<tr><td>'.__('Add email to meeting','bmlt-workflow').':</td><td>' . $result . '</td></tr>';
                    break;
                case "virtual_meeting_additional_info":
                    $table .= '<tr><td>'.__('Virtual Meeting Additional Info','bmlt-workflow').'</td><td>' . $value . '</td></tr>';
                    break;
                case "phone_meeting_number":
                    $table .= '<tr><td>'.__('Virtual Meeting Phone Details','bmlt-workflow').'</td><td>' . $value . '</td></tr>';
                    break;
                case "virtual_meeting_link":
                    $table .= '<tr><td>'.__('Virtual Meeting Link','bmlt-workflow').'</td><td>' . $value . '</td></tr>';
                    break;

                case "formatIds":
                    $friendlyname = __('Meeting Formats','bmlt-workflow');
                    // convert the meeting formats to human readable
                    $friendlydata = "";
                    foreach ($value as $key) {
                        $friendlydata .= "(" . $this->formats[$key]["key_string"] . ")-" . $this->formats[$key]["name_string"] . " ";
                    }
                    $table .= '<tr><td>'.__('Meeting Formats','bmlt-workflow').':</td><td>' . $friendlydata . '</td></tr>';
                    break;
            }
        }
        }

        return $table;
    }
}

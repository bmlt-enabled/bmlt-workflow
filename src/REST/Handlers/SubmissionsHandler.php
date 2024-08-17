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
        if (empty($intstub)) {
            // $this->debug_log("SubmissionsHandler: Creating new Integration");
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }
        
        // $this->debug_log("SubmissionsHandler: Creating new BMLTWF_Database");        
        $this->BMLTWF_Database = new BMLTWF_Database();
    
    }

    public function get_submissions_handler()
    {
        global $wpdb;

        // only show submissions we have access to
        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        if(current_user_can('manage_options'))
        {
            $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name);
        }
        else
        {
            $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' s inner join ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d', $current_uid);
        }
        // $this->debug_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        // $this->debug_log(($result));
        foreach ($result as $key => $value) {
            $result[$key]['changes_requested'] = json_decode($result[$key]['changes_requested'], true, 2);
        }
        return $result;
    }

    public function delete_submission_handler($request)
    {

        global $wpdb;

        $sql = $wpdb->prepare('DELETE FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $wpdb->query($sql, ARRAY_A);

        return $this->bmltwf_rest_success(__('Deleted submission id ','bmlt-workflow') . $request['id']);
    }

    public function get_submission_handler($request)
    {
        global $wpdb;

        $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    private function get_submission_id_with_permission_check($change_id)
    {
        global $wpdb;

        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        if(current_user_can('manage_options'))
        {
            $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' where id=%d', $change_id);
        }
        else
        {
            $sql = $wpdb->prepare('SELECT * FROM ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' s inner join ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d and s.id="%d" limit 1', $current_uid, $change_id);
        }
        // $this->debug_log($sql);
        $result = $wpdb->get_row($sql, ARRAY_A);
        // $this->debug_log("RESULT");
        // $this->debug_log(($result));
        if (empty($result)) {
            return $this->bmltwf_rest_error(__('Permission denied viewing submission id','bmlt-workflow')." {$change_id}", 403);
        }
        return $result;
    }

    public function reject_submission_handler($request)
    {
        global $wpdb;


        $change_id = $request->get_param('id');

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
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            'rejected',
            $username,
            current_time('mysql', true),
            $message,
            $request['id']
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


        $change_id = $request->get_param('id');

        $this->debug_log("patch request for id " . $change_id);

        // permitted change list from quickedit - notably no meeting id or service body
        $quickedit_change = $request->get_param('changes_requested');

        $change_subfields = array(
            "meeting_name",
            "start_time",
            "duration_time",
            "location_text",
            "location_street",
            "location_info",
            "location_municipality",
            "location_province",
            "location_postal_code_1",
            "weekday_tinyint",
            "format_shared_id_list",
            "location_sub_province",
            "location_nation",
            "virtual_meeting_additional_info",
            "phone_meeting_number",
            "virtual_meeting_link",
            "venue_type",
            "published",
            "virtualna_published",
            "latitude",
            "longitude"
        );

        foreach ($quickedit_change as $key => $value) {
            // $this->debug_log("checking " . $key);
            if ((!in_array($key, $change_subfields)) || (is_array($value))) {
                // $this->debug_log("removing " . $key);
                unset($quickedit_change[$key]);
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
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set changes_requested = "%s",change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            json_encode($merged_change),
            'updated',
            $username,
            current_time('mysql', true),
            NULL,
            $request['id']
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

        $this->debug_log("GMAPS location lookup returns = " . $location['latitude'] . " " . $location['longitude']);

        $latlng = array();
        $latlng['latitude'] = $location['results'][0]['geometry']['location']['lat'];
        $latlng['longitude'] = $location['results'][0]['geometry']['location']['lng'];
        return $latlng;
    }

    public function approve_submission_handler($request)
    {
        global $wpdb;

        // body parameters
        $params = $request->get_json_params();
        // url parameters from parsed route
        $change_id = $request->get_param('id');

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
            "meeting_name",
            "meeting_id",
            "start_time",
            "duration_time",
            "location_text",
            "location_street",
            "location_info",
            "location_municipality",
            "location_province",
            "location_postal_code_1",
            "weekday_tinyint",
            "service_body_bigint",
            "format_shared_id_list",
            "location_sub_province",
            "location_nation",
            "virtual_meeting_additional_info",
            "phone_meeting_number",
            "virtual_meeting_link",
            "venue_type",
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
                $change['published'] = 1;

                $response = $this->bmlt_integration->createMeeting($change);

                break;
            case 'reason_change':
                // needs an id_bigint not a meeting_id
                $change['id_bigint'] = $result['meeting_id'];

                $this->debug_log("CHANGE");
                $this->debug_log(($change));

                // geolocate based on changes - apply the changes to the BMLT version, then geolocate
                $bmlt_meeting = $this->bmlt_integration->retrieve_single_meeting($result['meeting_id']);
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
                $bmlt_venue_type = $bmlt_meeting['venue_type'];
                // $this->debug_log("bmlt_meeting[venue_type]=");
                // $this->debug_log($bmlt_venue_type);

                $change_venue_type = $change['venue_type'] ?? '0';
                // $this->debug_log("change[venue_type]=");
                // $this->debug_log($change_venue_type);

                $is_change_to_f2f = (($change_venue_type == 1)&&($bmlt_venue_type != $change_venue_type));
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

                if(array_key_exists('worldid_mixed',$bmlt_meeting) && array_key_exists('virtualna_published', $change))
                {
                    $this->debug_log("virtualna_published = ".$change['virtualna_published']);

                    $orig_worldid = $bmlt_meeting['worldid_mixed'];
                    $this->debug_log("original worldid = ".$orig_worldid);

                    if($change["virtualna_published"] === 1)
                    {
                        $change["worldid_mixed"] = substr_replace($orig_worldid, 'G', 0, 1);
                        unset($change["virtualna_published"]);
                    }
                    else
                    {
                        $change["worldid_mixed"] = substr_replace($orig_worldid, 'U', 0, 1);
                        unset($change["virtualna_published"]);
                    }
                    $this->debug_log("new worldid = ".$change["worldid_mixed"]);

                }

                $response = $this->bmlt_integration->updateMeeting($change);

                if (\is_wp_error(($response))) {
                    return $response;
                }

                break;
            case 'reason_close':

                $this->debug_log(($params));

                // are we doing a delete or an unpublish on close?
                if ((!empty($params['delete'])) && ($params['delete'] == "true")) {

                    $resp = $this->bmlt_integration->deleteMeeting($result['meeting_id']);

                    if (\is_wp_error(($resp))) {
                        return $resp;
                    }
                } else {
                    // unpublish by default
                    $change['published'] = 0;
                    $change['id_bigint'] = $result['meeting_id'];
                    $resp = $this->bmlt_integration->updateMeeting($change);

                    if (\is_wp_error(($resp))) {
                        return $resp;
                    }
                }

                break;

            default:
                return $this->bmltwf_rest_error(__('This change type cannot be approved','bmlt-workflow')." ({$submission_type})", 422);
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->BMLTWF_Database->bmltwf_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            'approved',
            $username,
            current_time('mysql', true),
            $message,
            $request['id']
        );

        $result = $wpdb->get_results($sql, ARRAY_A);

        $from_address = get_option('bmltwf_email_from_address');

        //
        // send action email
        //

        $to_address = $submitter_email;
        $subject = __('NA Meeting Change Request Approval - Submission ID','bmlt-workflow')." " . $request['id'];
        $body = __('Your meeting change has been approved - change ID','bmlt-workflow')." (" . $request['id'] . ")";
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
                    'meeting_name' => $change['meeting_name'],
                    'contact_number' => $starter_kit_contact_number);

                    $this->debug_log("We're sending a starter kit");
                    $template = get_option('bmltwf_fso_email_template');
                    if (!empty($template)) {
                        $subject = __('Starter Kit Request','bmlt-workflow');
                        $to_address = get_option('bmltwf_fso_email_address');
                        $fso_subfields = array('contact_number','submitter_name', 'meeting_name', 'starter_kit_postal_address');

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
        $sql = $wpdb->prepare('SELECT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' where service_body_bigint="%d"', $id);
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
            $venue_type = $data['venue_type'] ?? '0';
            $virtual_meeting_bool = ($venue_type !== '1');

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
            "meeting_id" => array("number", $reason_change_bool | $reason_close_bool),
            "first_name" => array("text", true),
            "last_name" => array("text", true),
            "meeting_name" => array("text", $reason_new_bool),
            "start_time" => array("time", $reason_new_bool),
            "duration_time" => array("time", $reason_new_bool),
            "venue_type" => array("venue", $reason_new_bool | $reason_change_bool),
            // location text and street only required if its not a virtual meeting #75
            "location_text" => array("text", $reason_new_bool && (!$virtual_meeting_bool)),
            "location_street" => array("text", $reason_new_bool && (!$virtual_meeting_bool)),
            "location_info" => array("text", false),
            "location_municipality" => array("text", $reason_new_bool),
            "weekday_tinyint" => array("weekday", $reason_new_bool),
            "service_body_bigint" => array("bigint", $reason_new_bool),
            "email_address" => array("email", true),
            "contact_number" => array("text", false),
            // optional #93
            "format_shared_id_list" => array("commaseperatednumbers",  $reason_new_bool && $require_meeting_formats),
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
            "virtualna_published" => array("boolnum", $reason_change_bool && $virtual_meeting_bool)
        );

        $sanitised_fields = array();

        // blank meeting id if not provided
        $sanitised_fields['meeting_id'] = 0;
        
        // sanitise all provided fields and drop all others
        foreach ($subfields as $field => $validation) {
            $field_type = $validation[0];
            $field_is_required = $validation[1];
            // if the form field is required, check if the submission is empty or non existent
            if ($field_is_required && !array_key_exists($field,$data)) {
                return $this->bmltwf_rest_error(__('Form field','bmlt-workflow').' "' . $field . '" '.__('is required','bmlt-workflow').'.', 422);
            }

            // special handling for temporary virtual
            if ($field === "venue_type" && $virtual_meeting_bool && ($data["update_reason"] !== 'reason_close'))
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
                    case ('yesno'):
                        if (($data[$field] !== 'yes') && ($data[$field] !== 'no')) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('commaseperatednumbers'):
                        if (preg_match("/[^0-9,]/", $data[$field])) {
                            return $this->invalid_form_field($field);
                        }
                        $data[$field] = trim($data[$field], ',');
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
                    case ('weekday'):
                        if (!(($data[$field] >= 1) && ($data[$field] <= 7))) {
                            return $this->invalid_form_field($field);
                        }
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

        if (empty($sanitised_fields['service_body_bigint'])) {
            // we should never have a blank service body unless it is 'other' request
            return $this->bmltwf_rest_error('Form field "service_body_bigint" is required.', 422);
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
                    "meeting_name",
                    "start_time",
                    "duration_time",
                    "location_text",
                    "location_street",
                    "location_info",
                    "location_municipality",
                    "location_province",
                    "location_postal_code_1",
                    "location_nation",
                    "location_sub_province",
                    "weekday_tinyint",
                    "service_body_bigint",
                    "format_shared_id_list",
                    "contact_number",
                    "group_relationship",
                    "add_contact",
                    "additional_info",
                    "virtual_meeting_additional_info",
                    "phone_meeting_number",
                    "virtual_meeting_link",
                    "starter_kit_required",
                    "starter_kit_postal_address",
                    "venue_type"
                );

                // new meeting - add all fields to the changes requested
                foreach ($allowed_fields as $field) {
                    // make sure its not a null entry, ie not entered on the frontend form
                    if (!empty($sanitised_fields[$field])) {
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }
                // always mark our submission as published for a new meeting
                $submission['published'] = 1;

                break;
            case ('reason_change'):

                // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "meeting_name",
                    "start_time",
                    "duration_time",
                    "location_text",
                    "location_street",
                    "location_info",
                    "location_municipality",
                    "location_province",
                    "location_postal_code_1",
                    "location_nation",
                    "location_sub_province",
                    "weekday_tinyint",
                    "service_body_bigint",
                    "format_shared_id_list",
                    "virtual_meeting_additional_info",
                    "phone_meeting_number",
                    "virtual_meeting_link",
                    "venue_type",
                    "published",
                );

                $allowed_fields_extra = array(
                    "contact_number",
                    "group_relationship",
                    "add_contact",
                    "additional_info",
                    "virtualna_published",
                );

                $bmlt_meeting = $this->bmlt_integration->retrieve_single_meeting($sanitised_fields['meeting_id']);
                if($this->bmlt_integration->is_v3_server())
                {
                    // change our meeting return to v2 format so we can handle original components
                    $bmlt_meeting = $this->bmlt_integration->convertv3meetingtov2($bmlt_meeting);
                }
                // $this->debug_log(($bmlt_meeting));
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
                // if the user submitted something different to what is in bmlt, save it in changes
                foreach ($allowed_fields as $field) {

                    $bmlt_field = $bmlt_meeting[$field] ?? false;

                    // if the field is blank in bmlt, but they submitted a change, add it to the list
                    if (!$bmlt_field && array_key_exists($field, $sanitised_fields) && !empty($sanitised_fields[$field])) {
                        $submission[$field] = $sanitised_fields[$field];
                        $submission_count++;

                    }
                    // if the field is in bmlt and its different to the submitted item, add it to the list
                    else {
                        if ($bmlt_field) {
                            // if the field they submitted is not blank, check whats submitted is different to the bmlt field
                            if (array_key_exists($field, $sanitised_fields)) {
                                if ($bmlt_meeting[$field] != $sanitised_fields[$field]) {
                                    // don't allow someone to modify a meeting service body
                                    if ($field === 'service_body_bigint') {
                                        return $this->bmltwf_rest_error(__('Service body cannot be changed.','bmlt-workflow'), 403);
                                    }
                                    $submission[$field] = $sanitised_fields[$field];
                                    $submission_count++;
                                }
                            }
                            else
                            // if they made the field entirely blank then it implies its different from the bmlt field
                            {
                                // don't allow someone to modify a meeting service body
                                if ($field === 'service_body_bigint') {
                                    return $this->bmltwf_rest_error(__('Service body cannot be changed.','bmlt-workflow'), 403);
                                }
                                $submission[$field] = "";
                                $submission_count++;
                            }
                        }
                    }

                    // store away the original meeting details so we know what changed
                    if ($bmlt_field)
                    {
                        $original_name = "original_".$field;
                        $submission[$original_name] = $bmlt_field;    
                    }
                }

                if (!$submission_count) {
                    return $this->bmltwf_rest_error(__('Nothing was changed.','bmlt-workflow'), 422);
                }

                // add in extra form fields (non BMLT fields) to the submission
                foreach ($allowed_fields_extra as $field) {
                    if (array_key_exists($field, $sanitised_fields)) {
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
                    "service_body_bigint",
                    "additional_info",
                );

                foreach ($allowed_fields as $item) {
                    if (isset($sanitised_fields[$item])) {
                        $submission[$item] = $sanitised_fields[$item];
                    }
                }

                // populate the meeting name/time/day so we dont need to do it again on the submission page
                $bmlt_meeting = $this->bmlt_integration->retrieve_single_meeting($sanitised_fields['meeting_id']);
                if($this->bmlt_integration->is_v3_server())
                {
                    // change our meeting return to v2 format so we can handle original components
                    $bmlt_meeting = $this->bmlt_integration->convertv3meetingtov2($bmlt_meeting);
                }

                if(\is_wp_error($bmlt_meeting))
                {
                    return $this->bmltwf_rest_error(__('Error retrieving meeting details','bmlt-workflow'), 422);
                }
                $this->debug_log("BMLT MEETING");
                $this->debug_log(($bmlt_meeting));

                $submission['meeting_name'] = $bmlt_meeting['meeting_name'];
                $submission['weekday_tinyint'] = $bmlt_meeting['weekday_tinyint'];
                $submission['start_time'] = $bmlt_meeting['start_time'];

                break;

            default:
                return $this->bmltwf_rest_error(__('Invalid meeting change','bmlt-workflow'), 422);
        }

        $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
        $submitter_email = $sanitised_fields['email_address'];

        // max size check for #7
        $chg = wp_json_encode($submission, 0, 1);

        if (strlen($chg) >= 2048) {
            return $this->bmltwf_rest_error(__('Meeting change request exceeds maximum size','bmlt-workflow'), 422);
        }

        // insert into submissions db
        global $wpdb;

        $wpdb->insert(
            $this->BMLTWF_Database->bmltwf_submissions_table_name,
            array(
                'submission_time'   => current_time('mysql', true),
                'meeting_id' => $sanitised_fields['meeting_id'],
                'submitter_name' => $submitter_name,
                'submission_type'  => $reason,
                'submitter_email' => $submitter_email,
                'changes_requested' => wp_json_encode($submission, 0, 1),
                'service_body_bigint' => $sanitised_fields['service_body_bigint']
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
            'meeting_id' => $sanitised_fields['meeting_id'],
            'submitter_name' => $submitter_name,
            'submission_type'  => $reason,
            'submitter_email' => $submitter_email,
            'changes_requested' => wp_json_encode($submission, 0, 1),
            'service_body_bigint' => $sanitised_fields['service_body_bigint']
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

        $to_address = $this->get_emails_by_servicebody_id($sanitised_fields['service_body_bigint']);
        $subject = '[bmlt-workflow] ' . $submission_type . ' '.__('request received','bmlt-workflow').' - ' . $sblist[$sanitised_fields['service_body_bigint']]['name'] . ' - '.__('Change ID','bmlt_workflow').' #' . $insert_id;
        $body = __('Log in to','bmlt-workflow').' <a href="' . get_site_url() . '/wp-admin/admin.php?page=bmltwf-submissions">'.__('BMLTWF Submissions Page','bmlt-workflow').'</a> to review.';
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
            switch ($key) {
                case "meeting_name":
                    $table .= '<tr><td>'.__('Meeting Name','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "start_time":
                    $table .= '<tr><td>'.__('Start Time','bmlt-workflow').':</td><td>' . $value . '</td></tr>';
                    break;
                case "duration_time":
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
                case "weekday_tinyint":
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

                case "format_shared_id_list":
                    $friendlyname = __('Meeting Formats','bmlt-workflow');
                    // convert the meeting formats to human readable
                    $friendlydata = "";
                    $strarr = explode(',', $value);
                    foreach ($strarr as $key) {
                        $friendlydata .= "(" . $this->formats[$key]["key_string"] . ")-" . $this->formats[$key]["name_string"] . " ";
                    }
                    $table .= '<tr><td>'.__('Meeting Formats','bmlt-workflow').':</td><td>' . $friendlydata . '</td></tr>';
                    break;
            }
        }

        return $table;
    }
}

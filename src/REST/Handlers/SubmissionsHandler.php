<?php

namespace wbw\REST\Handlers;

use wbw\BMLT\Integration;
use wbw\REST\HandlerCore;
use wbw\WBW_Database;
use wbw\WBW_Debug;

class SubmissionsHandler
{

    public function __construct($stub = null)
    {
        if (empty($stub)) {
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $stub;
        }
        $this->handlerCore = new HandlerCore();
        $this->wbw_dbg = new WBW_Debug();
        $this->WBW_Database = new WBW_Database();

    }

    public function get_submissions_handler()
    {

        global $wpdb;

        // only show submissions we have access to
        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        $sql = $wpdb->prepare('SELECT * FROM ' . $this->WBW_Database->wbw_submissions_table_name . ' s inner join ' . $this->WBW_Database->wbw_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d', $current_uid);
        // $this->wbw_dbg->debug_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($result));
        foreach ($result as $key => $value) {
            $result[$key]['changes_requested'] = json_decode($result[$key]['changes_requested'], true, 2);
        }
        return $result;
    }

    public function delete_submission_handler($request)
    {

        global $wpdb;

        $sql = $wpdb->prepare('DELETE FROM ' . $this->WBW_Database->wbw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $wpdb->query($sql, ARRAY_A);

        return $this->handlerCore->wbw_rest_success('Deleted submission id ' . $request['id']);
    }

    public function get_submission_handler($request)
    {
        global $wpdb;

        $sql = $wpdb->prepare('SELECT * FROM ' . $this->WBW_Database->wbw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    private function get_submission_id_with_permission_check($change_id)
    {
        global $wpdb;
        

        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        $sql = $wpdb->prepare('SELECT * FROM ' . $this->WBW_Database->wbw_submissions_table_name . ' s inner join ' . $this->WBW_Database->wbw_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d and s.id="%d" limit 1', $current_uid, $change_id);
        // $this->wbw_dbg->debug_log($sql);
        $result = $wpdb->get_row($sql, ARRAY_A);
        $this->wbw_dbg->debug_log("RESULT");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($result));
        if (empty($result)) {
            return $this->handlerCore->wbw_rest_error("Permission denied viewing submission id {$change_id}", 403);
        }
        return $result;
    }

    public function reject_submission_handler($request)
    {
        global $wpdb;
        

        $change_id = $request->get_param('id');

        $this->wbw_dbg->debug_log("rejection request for id " . $change_id);

        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        $submitter_email = $result['submitter_email'];
        $change_made = $result['change_made'];

        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->handlerCore->wbw_rest_error("Submission id {$change_id} is already $change_made", 422);
        }

        $params = $request->get_json_params();
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->handlerCore->wbw_rest_error('Reject message must be less than 1024 characters', 422);
            }
        } else {
            $this->wbw_dbg->debug_log("action message is null");
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->WBW_Database->wbw_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
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

        $from_address = $this->WBW_WP_Options->wbw_get_option('wbw_email_from_address');
        $to_address = $submitter_email;
        $subject = "NA Meeting Change Request Rejection - Submission ID " . $request['id'];
        $body = "Your meeting change (ID " . $request['id'] . ") has been rejected.";
        if (!empty($message)) {
            $body .= "<br><br>Message from trusted servant:<br><br>" . $message;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->wbw_dbg->debug_log("Rejection email");
        $this->wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $this->wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        return $this->handlerCore->wbw_rest_success('Rejected submission id ' . $change_id);
    }

    public function patch_submission_handler($request)
    {
        global $wpdb;
        

        $change_id = $request->get_param('id');

        $this->wbw_dbg->debug_log("patch request for id " . $change_id);

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
            "virtual_meeting_additional_info", 
            "phone_meeting_number", 
            "virtual_meeting_link"
        );

        foreach ($quickedit_change as $key => $value) {
            // $this->wbw_dbg->debug_log("checking " . $key);
            if ((!in_array($key, $change_subfields)) || (is_array($value))) {
                // $this->wbw_dbg->debug_log("removing " . $key);
                unset($quickedit_change[$key]);
            }
        }

        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        // $sql = $wpdb->prepare('SELECT * FROM ' . $wbw_submissions_table_name . ' where id="%d" limit 1', $change_id);
        // $result = $wpdb->get_row($sql, ARRAY_A);

        $change_made = $result['change_made'];

        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->handlerCore->wbw_rest_error("Submission id {$change_id} is already $change_made", 422);
        }
        // $this->wbw_dbg->debug_log("change made is ".$change_made);

        // get our saved changes from the db
        $saved_change = json_decode($result['changes_requested'], 1);

        // put the quickedit ones over the top

        // $this->wbw_dbg->debug_log("merge before - saved");
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($saved_change));
        // $this->wbw_dbg->debug_log("merge before - quickedit");
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($quickedit_change));

        $merged_change = array_merge($saved_change, $quickedit_change);

        // $this->wbw_dbg->debug_log("merge after - saved");
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($merged_change));

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->WBW_Database->wbw_submissions_table_name . ' set changes_requested = "%s",change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            json_encode($merged_change),
            'updated',
            $username,
            current_time('mysql', true),
            NULL,
            $request['id']
        );
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($sql));

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $this->handlerCore->wbw_rest_success('Updated submission id ' . $change_id);
    }

    private function do_geolocate($change)
    {
        // workaround for server side geolocation
        
        $locfields = array("location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation");
        $locdata = array();
        foreach($locfields as $field)
        {
            if(!empty($change[$field]))
            {
                $locdata[]=$change[$field];
            }
        }
        $locstring = implode(', ',$locdata);
        $this->wbw_dbg->debug_log("GMAPS location lookup = " . $locstring);

        $location = $this->bmlt_integration->geolocateAddress($locstring);
        if (is_wp_error($location)) {
            return $location;
        }

        $this->wbw_dbg->debug_log("GMAPS location lookup returns = " . $location['latitude'] . " " . $location['longitude']);

        $latlng = array();
        $latlng['latitude']= $location['latitude'];
        $latlng['longitude']= $location['longitude'];
        return $latlng;

    }

    public function approve_submission_handler($request)
    {
        global $wpdb;
        

        $this->wbw_dbg->debug_log("REQUEST");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));
        // body parameters
        $params = $request->get_json_params();
        // url parameters from parsed route
        $change_id = $request->get_param('id');

        // clean/validate supplied approval message
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->handlerCore->wbw_rest_error('Approve message must be less than 1024 characters', 422);
            }
        }

        // retrieve our submission id from the one specified in the route
        $this->wbw_dbg->debug_log("getting changes for id " . $change_id);
        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        // can't approve an already actioned submission
        $change_made = $result['change_made'];
        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->handlerCore->wbw_rest_error("Submission id {$change_id} is already {$change_made}", 422);
        }

        $change = json_decode($result['changes_requested'], 1);

        // handle request to add email
        $submitter_email = $result['submitter_email'];
        $add_email = false;
        if ((!empty($change['add_email'])) && ($change['add_email'] === 'yes')) {
            $add_email = true;
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
            "virtual_meeting_link"
        );

        foreach ($change as $key => $value) {
            if (!in_array($key, $change_subfields)) {
                unset($change[$key]);
            }
        }

        if ($add_email === true) {
            $change['contact_email_1'] = $submitter_email;
        }

        // $this->wbw_dbg->debug_log("json decoded");
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($change));

        // approve based on different change types
        $submission_type = $result['submission_type'];
        $this->wbw_dbg->debug_log("change type = " . $submission_type);
        switch ($submission_type) {
            case 'reason_new':
                // workaround for semantic new meeting bug
                $change['id_bigint'] = 0;

                // run our geolocator on the address
                $latlng = $this->do_geolocate($change);
                if (is_wp_error($latlng)) {
                    return $latlng;
                }

                $change['latitude']= $latlng['latitude'];
                $change['longitude']= $latlng['longitude'];

                // handle publish/unpublish here
                $change['published'] = 1;
                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                if (is_wp_error($response)) {
                    return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                }

                break;
            case 'reason_change':
                // needs an id_bigint not a meeting_id
                $change['id_bigint'] = $result['meeting_id'];

                $this->wbw_dbg->debug_log("CHANGE");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($change));

                // geolocate based on changes - apply the changes to the BMLT version, then geolocate
                $bmlt_meeting = $this->bmlt_integration->retrieve_single_meeting($result['meeting_id']);
                if (is_wp_error($bmlt_meeting)) {
                    return $this->handlerCore->wbw_rest_error("BMLT Lookup Error - Couldn't find this meeting Id", 500);
                }

                $locfields = array("location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation");

                foreach($locfields as $field)
                {
                    if(!empty($change[$field]))
                    {
                        $bmlt_meeting[$field]=$change[$field];
                    }
                }
        
                $latlng = $this->do_geolocate($bmlt_meeting);
                if (is_wp_error($latlng)) {
                    return $latlng;
                }
                // add the new geo to the original change
                $change['latitude']= $latlng['latitude'];
                $change['longitude']= $latlng['longitude'];

                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                if (is_wp_error($response)) {
                    return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                }
                $this->wbw_dbg->debug_log("response");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
                $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
                $this->wbw_dbg->debug_log("arr");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($arr));
                $this->wbw_dbg->debug_log("change");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($change));
                // the response back from BMLT doesnt even match what we are trying to change
                if ((!empty($arr['id_bigint'])) && ($arr['id_bigint'] != $change['id_bigint'])) {
                    return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Meeting change failed', 500);
                }

                break;
            case 'reason_close':

                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($params));

                // are we doing a delete or an unpublish on close?
                if ((!empty($params['delete'])) && ($params['delete'] == "true")) {
                    $changearr = array();
                    $changearr['bmlt_ajax_callback'] = 1;
                    $changearr['delete_meeting'] = $result['meeting_id'];
                    // response message {'success':true,'report':'3557'}
                    $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                    if (is_wp_error($response)) {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                    }

                    $json = wp_remote_retrieve_body($response);
                    $rep = str_replace("'",'"',$json);

                    $arr = json_decode($rep, true);

                    $this->wbw_dbg->debug_log("DELETE RESPONSE");
                    $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($arr));

                    if ((isset($arr['success'])) && ($arr['success'] !== true)) {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Meeting deletion failed', 500);
                    }
                    if ((!empty($arr['report'])) && ($arr['report'] != $change['id_bigint'])) {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Meeting deletion failed', 500);
                    }

                } else {
                    // unpublish by default
                    $change['published'] = 0;

                    $changearr = array();
                    $changearr['bmlt_ajax_callback'] = 1;
                    $change['id_bigint']=$result['meeting_id'];
                    $changearr['set_meeting_change'] = json_encode($change);
                    $this->wbw_dbg->debug_log("UNPUBLISH");
                    $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($changearr));

                    $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                    if (is_wp_error($response)) {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                    }

                    $this->wbw_dbg->debug_log("UNPUBLISH RESPONSE");
                    $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));

                    $json = wp_remote_retrieve_body($response);
                    $rep = str_replace("'",'"',$json);

                    $dec = json_decode($rep, true);
                    if (((isset($dec['error'])) && ($dec['error'] === true)) || (empty($dec[0])))
                    {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Meeting unpublish failed', 500);
                    }

                    $arr = $dec[0];

                    if ((isset($arr['published'])) && ($arr['published'] != 0)) {
                        return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Meeting unpublish failed', 500);
                    }
                }

                break;
            case 'reason_other': {
                    break;
                }

            default:
                return $this->handlerCore->wbw_rest_error("This change type ({$submission_type}) cannot be approved", 422);
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $this->WBW_Database->wbw_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            'approved',
            $username,
            current_time('mysql', true),
            $message,
            $request['id']
        );

        $result = $wpdb->get_results($sql, ARRAY_A);

        $from_address = $this->WBW_WP_Options->wbw_get_option('wbw_email_from_address');

        //
        // send action email
        //

        $to_address = $submitter_email;
        $subject = "NA Meeting Change Request Approval - Submission ID " . $request['id'];
        $body = "Your meeting change (ID " . $request['id'] . ") has been approved.";
        if (!empty($message)) {
            $body .= "<br><br>Message from trusted servant:<br><br>" . $message;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->wbw_dbg->debug_log("Approval email");
        $this->wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $this->wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        //
        // send fso email
        //

        if ($submission_type == "reason_new") {
            if ((!empty($change['starter_kit_required'])) && ($change['starter_kit_required'] === 'yes') && (!empty($change['starter_kit_postal_address']))) {
                $this->wbw_dbg->debug_log("We're sending a starter kit");
                $template = $this->WBW_WP_Options->wbw_get_option('wbw_fso_email_template');
                if (!empty($template)) {
                    $subject = 'Starter Kit Request';
                    $to_address = $this->WBW_WP_Options->wbw_get_option('wbw_fso_email_address');
                    $fso_subfields = array('first_name', 'last_name', 'meeting_name', 'starter_kit_postal_address');

                    foreach ($fso_subfields as $field) {
                        $subfield = '{field:' . $field . '}';
                        if (!empty($change[$field])) {
                            $subwith = $change[$field];
                        } else {
                            $subwith = '(blank)';
                        }
                        $template = str_replace($subfield, $subwith, $template);
                    }
                    $body = $template;
                    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
                    $this->wbw_dbg->debug_log("FSO email");
                    $this->wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $this->wbw_dbg->vdump($headers));

                    wp_mail($to_address, $subject, $body, $headers);
                } else {
                    $this->wbw_dbg->debug_log("FSO email is empty");
                }
            }
        }

        return $this->handlerCore->wbw_rest_success('Approved submission id ' . $change_id);
    }


    private function get_emails_by_servicebody_id($id)
    {
        global $wpdb;

        $emails = array();
        $sql = $wpdb->prepare('SELECT wp_uid from ' . $this->WBW_Database->wbw_service_bodies_access_table_name . ' where service_body_bigint="%d"', $id);
        $result = $wpdb->get_col($sql);
        foreach ($result as $key => $value) {
            $user = get_user_by('ID', $value);
            $emails[] = $user->user_email;
        }
        return implode(',', $emails);
    }

    private function invalid_form_field($field)
    {
        return $this->handlerCore->wbw_rest_error('Form field "' . $field . '" is invalid.', 422);
    }



    public function meeting_update_form_handler_rest($data)
    {
        

        $this->wbw_dbg->debug_log("in rest handler");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($data));
        $reason_new_bool = false;
        $reason_other_bool = false;
        $reason_change_bool = false;
        $reason_close_bool = false;

        // strip blanks
        foreach ($data as $key => $value) {
            if (($data[$key] === "") || ($data[$key] === NULL)) {
                unset($data[$key]);
            }
        }

        if (isset($data['update_reason'])) {
            // we use these to enforce required parameters in the next section
            $reason_new_bool = ($data['update_reason'] === 'reason_new');
            $reason_other_bool = ($data['update_reason'] === 'reason_other');
            $reason_change_bool = ($data['update_reason'] === 'reason_change');
            $reason_close_bool = ($data['update_reason'] === 'reason_close');
        }

        if (!(isset($data['update_reason']) || (!$reason_new_bool && !$reason_other_bool && !$reason_change_bool && !$reason_close_bool))) {
            return $this->handlerCore->wbw_rest_error('No valid meeting update reason provided', 422);
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
            "location_text" => array("text", $reason_new_bool),
            "location_street" => array("text", $reason_new_bool),
            "location_info" => array("text", false),
            "location_municipality" => array("text", $reason_new_bool),
            "location_province" => array("text", $reason_new_bool),
            "location_postal_code_1" => array("number", $reason_new_bool),
            "weekday_tinyint" => array("weekday", $reason_new_bool),
            "service_body_bigint" => array("bigint", $reason_new_bool),
            "email_address" => array("email", true),
            "contact_number_confidential" => array("text", false),
            "format_shared_id_list" => array("commaseperatednumbers",  $reason_new_bool),
            "additional_info" => array("textarea", $reason_close_bool),
            "starter_kit_postal_address" => array("textarea", false),
            "starter_kit_required" => array("text", $reason_new_bool),
            "other_reason" => array("textarea", $reason_other_bool),
            "location_sub_province" => array("text", false),
            "location_nation" => array("text", false),
            "group_relationship" => array("text", true),
            "add_email" => array("yesno", true),
            "virtual_meeting_additional_info" => array("text", false),
            "phone_meeting_number" => array("text", false),
            "virtual_meeting_link" => array("url", false),
        );

        $sanitised_fields = array();

        // blank meeting id if not provided
        $sanitised_fields['meeting_id'] = 0;

        // sanitise all provided fields and drop all others
        foreach ($subfields as $field => $validation) {
            $field_type = $validation[0];
            $field_is_required = $validation[1];
            // if the form field is required, check if the submission is empty or non existent
            if ($field_is_required && empty($data[$field])) {
                return $this->handlerCore->wbw_rest_error('Form field "' . $field . '" is required.', 422);
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
                    case ('number'):
                    case ('bigint'):
                        $data[$field] = intval($data[$field]);
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
                        if(strlen($data[$field])>512) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    case ('time'):
                        if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:00$/', $data[$field])) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    default:
                        return $this->handlerCore->wbw_rest_error('Form processing error', 500);
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

        if (empty($sanitised_fields['service_body_bigint']))
        {
            // we should never have a blank service body unless it is 'other' request
            if ($reason !== 'reason_other')
            {
                return $this->handlerCore->wbw_rest_error('Form field "service_body_bigint" is required.', 422);
            }
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
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "additional_info",
                    "virtual_meeting_additional_info",
                    "phone_meeting_number",
                    "virtual_meeting_link",
                    "starter_kit_required",
                    "starter_kit_postal_address"
                );

                // new meeting - add all fields to the changes requested
                foreach ($allowed_fields as $field) {
                    // make sure its not a null entry, ie not entered on the frontend form
                    if (!empty($sanitised_fields[$field])) {
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }

                break;
            case ('reason_change'):

                // change meeting - just add the deltas. no real reason to do this as bmlt result would be the same, but safe to filter it regardless
                // $subject = 'Change meeting notification';

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
                    "virtual_meeting_link"

                );

                $allowed_fields_extra = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "additional_info",
                );

                $bmlt_meeting = $this->bmlt_integration->retrieve_single_meeting($sanitised_fields['meeting_id']);
                // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($meeting));
                if (is_wp_error($bmlt_meeting)) {
                    return $this->handlerCore->wbw_rest_error('Internal BMLT error.', 500);
                }
                // strip blanks from BMLT
                foreach ($bmlt_meeting as $key => $value) {
                    if (($bmlt_meeting[$key] === "") || ($bmlt_meeting[$key] === NULL)) {
                        unset($bmlt_meeting[$key]);
                    }
                }

                // if the user submitted something different to what is in bmlt, save it in changes
                foreach ($allowed_fields as $field) {
                    // if the field is blank in bmlt, but they submitted a change, add it to the list
                    if ((empty($bmlt_meeting[$field])) && (!empty($sanitised_fields[$field]))) {
                        $this->wbw_dbg->debug_log("found a blank bmlt entry " . $field);
                        $submission[$field] = $sanitised_fields[$field];
                    }
                    // if the field is in bmlt and its different to the submitted item, add it to the list
                    else if ((!empty($bmlt_meeting[$field])) && (!empty($sanitised_fields[$field]))) {
                        if ($bmlt_meeting[$field] != $sanitised_fields[$field]) {
                            // don't allow someone to modify a meeting service body
                            if ($field === 'service_body_bigint') {
                                return $this->handlerCore->wbw_rest_error('Service body cannot be changed.', 403);
                            }
                            $submission[$field] = $sanitised_fields[$field];
                        }
                    }
                }

                if (!count($submission)) {
                    return $this->handlerCore->wbw_rest_error('Nothing was changed.', 422);
                }

                // add in extra form fields (non BMLT fields) to the submission
                foreach ($allowed_fields_extra as $field) {
                    if (!empty($sanitised_fields[$field])) {
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }

                $this->wbw_dbg->debug_log("SUBMISSION");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($submission));
                $this->wbw_dbg->debug_log("BMLT MEETING");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($bmlt_meeting));
                // store away the original meeting name so we know what changed
                $submission['original_meeting_name'] = $bmlt_meeting['meeting_name'];
                $submission['original_weekday_tinyint'] = $bmlt_meeting['weekday_tinyint'];
                $submission['original_start_time'] = $bmlt_meeting['start_time'];

                break;
            case ('reason_close'):
                // $subject = 'Close meeting notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
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
                $this->wbw_dbg->debug_log("BMLT MEETING");
                $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($bmlt_meeting));

                $submission['meeting_name'] = $bmlt_meeting['meeting_name'];
                $submission['weekday_tinyint'] = $bmlt_meeting['weekday_tinyint'];
                $submission['start_time'] = $bmlt_meeting['start_time'];

                break;
            case ('reason_other'):
                // $subject = 'Other notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "other_reason",
                    "service_body_bigint",
                );

                foreach ($allowed_fields as $item) {
                    if (isset($sanitised_fields[$item])) {
                        $submission[$item] = $sanitised_fields[$item];
                    }
                }
                break;
            default:
                return $this->handlerCore->wbw_rest_error('Invalid meeting change', 422);
        }

        $this->wbw_dbg->debug_log("SUBMISSION");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($submission));

        $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
        $submitter_email = $sanitised_fields['email_address'];

        // max size check for #7
        $chg = wp_json_encode($submission, 0, 1);
        
        if(strlen($chg)>=2048)
        {
            return $this->handlerCore->wbw_rest_error('Meeting change request exceeds maximum size', 422); 
        }

        // insert into submissions db
        global $wpdb;

        $wpdb->insert(
            $this->WBW_Database->wbw_submissions_table_name,
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
        $insert_id = $wpdb->insert_id;
        // $this->wbw_dbg->debug_log("id = " . $insert_id);
        $message = array(
            "message" => 'Form submission successful, submission id ' . $insert_id,
            "form_html" => '<h3>Form submission successful, your submission id  is #' . $insert_id . '. You will also receive an email confirmation of your submission.</h3>'
        );

        // Send our emails out

        // Common email fields
        $from_address = $this->WBW_WP_Options->wbw_get_option('wbw_email_from_address');

        /*
        * Send a notification to the configured trusted servants for the correct service body
        */
        
        switch ($reason) {
            case "reason_new":
                $submission_type = "New Meeting";
                break;
            case "reason_close":
                $submission_type = "Close Meeting";
                break;
            case "reason_change":
                $submission_type = "Modify Meeting";
                break;
            case "reason_other":
                $submission_type = "Other Request";
                break;
        }

        $to_address = $this->get_emails_by_servicebody_id($sanitised_fields['service_body_bigint']);
        $subject = '[bmlt-workflow] ' . $submission_type . ' request received - ID ' . $insert_id;
        $body = 'Log in to <a href="' . get_site_url() . '/wp-admin/admin.php?page=wbw-submissions">WBW Submissions Page</a> to review.';
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        wp_mail($to_address, $subject, $body, $headers);

        /*
        * Send acknowledgement email to the submitter
        */

        $to_address = $submitter_email;
        $subject = "NA Meeting Change Request Acknowledgement - Submission ID " . $insert_id;

        $template = $this->WBW_WP_Options->wbw_get_option('wbw_submitter_email_template');

        $subfield = '{field:submission}';
        $subwith = $this->submission_format($submission);
        $template = str_replace($subfield, $subwith, $template);

        $body = $template;

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $this->wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $this->wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        return $this->handlerCore->wbw_rest_success($message);
    }

    private function submission_format($submission)
    {

        $formats = $this->bmlt_integration->getMeetingFormats();

        $table = '';

        foreach ($submission as $key => $value) {
            switch ($key) {
                case "meeting_name":
                    $table .= '<tr><td>Meeting Name:</td><td>' . $value . '</td></tr>';
                    break;
                case "start_time":
                    $table .= '<tr><td>Start Time:</td><td>' . $value . '</td></tr>';
                    break;
                case "duration_time":
                    $table .= '<tr><td>Duration:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_text":
                    $table .= '<tr><td>Location:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_street":
                    $table .= '<tr><td>Street:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_info":
                    $table .= '<tr><td>Location Info:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_municipality":
                    $table .= '<tr><td>Municipality:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_province":
                    $table .= '<tr><td>Province/State:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_sub_province":
                    $table .= '<tr><td>SubProvince:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_nation":
                    $table .= '<tr><td>Nation:</td><td>' . $value . '</td></tr>';
                    break;
                case "location_postal_code_1":
                    $table .= '<tr><td>PostCode:</td><td>' . $value . '</td></tr>';
                    break;
                case "group_relationship":
                    $table .= '<tr><td>Relationship to Group:</td><td>' . $value . '</td></tr>';
                    break;
                case "weekday_tinyint":
                    $weekdays = ["Error", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                    $table .= "<tr><td>Meeting Day:</td><td>" . $weekdays[$value] . '</td></tr>';
                    break;
                case "additional_info":
                    $table .= '<tr><td>Additional Info:</td><td>' . $value . '</td></tr>';
                    break;
                case "other_reason":
                    $table .= '<tr><td>Other Reason:</td><td>' . $value . '</td></tr>';
                    break;
                case "contact_number_confidential":
                    $table .= "<tr><td>Contact number (confidential):</td><td>" . $value . '</td></tr>';
                    break;
                case "add_email":
                    $result = ($value === 'yes' ? 'Yes' : 'No');
                    $table .= '<tr><td>Add email to meeting:</td><td>' . $result . '</td></tr>';
                    break;
                case "virtual_meeting_additional_info":
                    $table .= '<tr><td>Virtual Meeting Additional Info</td><td>' . $value . '</td></tr>';
                    break;
                case "phone_meeting_number":
                    $table .= '<tr><td>Virtual Meeting Phone Details</td><td>' . $value . '</td></tr>';
                    break;
                case "virtual_meeting_link":
                    $table .= '<tr><td>Virtual Meeting Link</td><td>' . $value . '</td></tr>';
                    break;

                case "format_shared_id_list":
                    $friendlyname = "Meeting Formats";
                    // convert the meeting formats to human readable
                    $friendlydata = "";
                    $strarr = explode(',', $value);
                    foreach ($strarr as $key) {
                        $friendlydata .= "(" . $formats[$key]["key_string"] . ")-" . $formats[$key]["name_string"] . " ";
                    }
                    $table .= "<tr><td>Meeting Formats:</td><td>" . $friendlydata . '</td></tr>';
                    break;
            }
        }

        return $table;
    }
}

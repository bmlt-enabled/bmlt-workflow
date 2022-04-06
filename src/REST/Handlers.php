<?php

namespace wbw\REST;

if (!defined('ABSPATH')) exit; // die if being called directly

use wbw\Debug;
use wbw\BMLT\Integration;

class Handlers
{

    public function __construct()
    {
        $this->bmlt_integration = new Integration;
    }

    // accepts raw string or array
    private function wbw_rest_success($message)
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

    private function wbw_rest_error($message, $code)
    {
        return new \WP_Error('wbw_error', $message, array('status' => $code));
    }

    private function wbw_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('wbw_error', $message, $data);
    }

    public function get_submissions_handler()
    {

        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_service_bodies_access_table_name;

        // only show submissions we have access to
        // select * from wp_wbw_submissions s inner join wp_wbw_service_bodies_access a on s.service_body_bigint = a.service_body_bigint where a.wp_uid = 1
        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        $sql = $wpdb->prepare('SELECT * FROM ' . $wbw_submissions_table_name . ' s inner join ' . $wbw_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d', $current_uid);
        // $wbw_dbg->debug_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        // $wbw_dbg->debug_log($wbw_dbg->vdump($result));
        foreach ($result as $key => $value) {
            $result[$key]['changes_requested'] = json_decode($result[$key]['changes_requested'], true, 2);
        }
        return $result;
    }

    public function get_service_bodies_handler($request)
    {

        global $wpdb;
        global $wbw_service_bodies_table_name;
        global $wbw_service_bodies_access_table_name;
        global $wbw_dbg;

        $params = $request->get_params();
        $wbw_dbg->debug_log($wbw_dbg->vdump($params));
        // $wbw_dbg->debug_log("params detail".$params['detail']);
        // only an admin can get the service areas detail (permissions) information
        if ((!empty($params['detail'])) && ($params['detail'] == "true") && (current_user_can('manage_options'))) {
            // do detail lookup

            $sblist = array();

            $req = array();
            $req['admin_action'] = 'get_service_body_info';
            $req['flat'] = '';

            // get an xml for a workaround
            $response = $this->bmlt_integration->postAuthenticatedRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
            if (is_wp_error($response)) {
                return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
            }

            $xml = simplexml_load_string($response['body']);
            $arr = json_decode(json_encode($xml), 1);

            // $wbw_dbg->debug_log($wbw_dbg->vdump($arr));

            $idlist = array();

            // make our list of service bodies
            foreach ($arr['service_body'] as $key => $value) {
                // $wbw_dbg->debug_log("looping key = " . $key);
                if (array_key_exists('@attributes', $value)) {
                    $sbid = $value['@attributes']['id'];
                    $idlist[] = $sbid;
                    $sblist[$sbid] = array('name' => $value['@attributes']['name']);
                } else {
                    // we need a name at minimum
                    break;
                }
                $sblist[$sbid]['contact_email'] = '';
                if (array_key_exists('contact_email', $value)) {
                    $sblist[$sbid]['contact_email'] = $value['contact_email'];
                }
            }

            // update our service area list in the database in case there have been some new ones added
            // $wbw_dbg->debug_log("get ids");
            $sqlresult = $wpdb->get_col('SELECT service_body_bigint FROM ' . $wbw_service_bodies_table_name . ';', 0);

            // $wbw_dbg->debug_log($wbw_dbg->vdump($sqlresult));
            $missing = array_diff($idlist, $sqlresult);
            // $wbw_dbg->debug_log("missing ids");
            // $wbw_dbg->debug_log($wbw_dbg->vdump($missing));

            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s", service_body_bigint="%d", show_on_form=0', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }
            // update any values that may have changed since last time we looked

            foreach ($idlist as $value) {
                $sql = $wpdb->prepare('UPDATE ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s" where service_body_bigint="%d"', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }

            // $wbw_dbg->debug_log("our sblist");
            // $wbw_dbg->debug_log($wbw_dbg->vdump($sblist));

            // make our group membership lists
            foreach ($sblist as $key => $value) {
                $wbw_dbg->debug_log("getting memberships for " . $key);
                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $wbw_service_bodies_access_table_name . ' where service_body_bigint = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                // $wbw_dbg->debug_log($wbw_dbg->vdump($result));
                $sblist[$key]['membership'] = implode(',', $result);
            }
            // get the form display settings
            $sqlresult = $wpdb->get_results('SELECT service_body_bigint,show_on_form FROM ' . $wbw_service_bodies_table_name, ARRAY_A);

            foreach ($sqlresult as $key => $value) {
                $bool = $value['show_on_form'] ? (true) : (false);
                $sblist[$value['service_body_bigint']]['show_on_form'] = $bool;
            }
        } else {
            // simple


            $sblist = array();
            // $wbw_dbg->debug_log("simple list of service areas and names");
            $result = $wpdb->get_results('SELECT * from ' . $wbw_service_bodies_table_name . ' where show_on_form != "0"', ARRAY_A);
            // $wbw_dbg->debug_log($wbw_dbg->vdump($result));
            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value) {
                $sblist[$value['service_body_bigint']]['name'] = $value['service_area_name'];
            }
            // $wbw_dbg->debug_log($wbw_dbg->vdump($sblist));

        }

        return $sblist;
    }

    public function post_service_bodies_handler($request)
    {
        global $wpdb;
        global $wbw_service_bodies_access_table_name;
        global $wbw_capability_manage_submissions;
        global $wbw_service_bodies_table_name;
        global $wbw_dbg;

        // $wbw_dbg->debug_log("request body");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($request->get_json_params()));
        $permissions = $request->get_json_params();
        // clear out our old permissions
        $wpdb->query('DELETE from ' . $wbw_service_bodies_access_table_name);
        // insert new permissions from form
        foreach ($permissions as $sb => $arr) {
            $members = $arr['membership'];
            foreach ($members as $member) {
                $sql = $wpdb->prepare('INSERT into ' . $wbw_service_bodies_access_table_name . ' SET wp_uid = "%d", service_body_bigint="%d"', $member, $sb);
                $wpdb->query($sql);
            }
            // update show/hide
            $show_on_form = $arr['show_on_form'];
            $sql = $wpdb->prepare('UPDATE ' . $wbw_service_bodies_table_name . ' SET show_on_form = "%d" where service_body_bigint="%d"', $show_on_form, $sb);
            $wpdb->query($sql);
        }

        // add / remove user capabilities
        $users = get_users();
        $result = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $wbw_service_bodies_access_table_name, 0);
        // $wbw_dbg->debug_log($wbw_dbg->vdump($sql));
        // $wbw_dbg->debug_log($wbw_dbg->vdump($result));
        foreach ($users as $user) {
            $wbw_dbg->debug_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($wbw_capability_manage_submissions);
                // $wbw_dbg->debug_log("adding cap");
            } else {
                $user->remove_cap($wbw_capability_manage_submissions);
                // $wbw_dbg->debug_log("removing cap");
            }
        }

        return $this->wbw_rest_success('Updated Service Areas');
    }


    public function delete_submission_handler($request)
    {

        global $wpdb;
        global $wbw_submissions_table_name;

        $sql = $wpdb->prepare('DELETE FROM ' . $wbw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $wpdb->query($sql, ARRAY_A);

        return $this->wbw_rest_success('Deleted submission id ' . $request['id']);
    }

    public function get_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;
        $sql = $wpdb->prepare('SELECT * FROM ' . $wbw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    private function get_submission_id_with_permission_check($change_id)
    {
        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_service_bodies_access_table_name;
        global $wbw_dbg;

        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        $sql = $wpdb->prepare('SELECT * FROM ' . $wbw_submissions_table_name . ' s inner join ' . $wbw_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d and s.id="%d" limit 1', $current_uid, $change_id);
        // $wbw_dbg->debug_log($sql);
        $result = $wpdb->get_row($sql, ARRAY_A);
        $wbw_dbg->debug_log("RESULT");
        $wbw_dbg->debug_log($wbw_dbg->vdump($result));
        if (empty($result)) {
            return $this->wbw_rest_error("Permission denied viewing submission id {$change_id}", 400);
        }
        return $result;
    }

    public function reject_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_dbg;

        $change_id = $request->get_param('id');

        $wbw_dbg->debug_log("rejection request for id " . $change_id);

        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        $submitter_email = $result['submitter_email'];
        $change_made = $result['change_made'];

        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->wbw_rest_error("Submission id {$change_id} is already $change_made", 400);
        }

        $params = $request->get_json_params();
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->wbw_rest_error('Reject message must be less than 1024 characters', 400);
            }
        } else {
            $wbw_dbg->debug_log("action message is null");
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $wbw_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
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

        $from_address = get_option('wbw_email_from_address');
        $to_address = $submitter_email;
        $subject = "NA Meeting Change Request Rejection - Submission ID " . $request['id'];
        $body = "Your meeting change (ID " . $request['id'] . ") has been rejected.";
        if (!empty($message)) {
            $body .= "<br><br>Message from trusted servant:<br><br>" . $message;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $wbw_dbg->debug_log("Rejection email");
        $wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        return $this->wbw_rest_success('Rejected submission id ' . $change_id);
    }

    public function patch_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_dbg;

        $change_id = $request->get_param('id');

        $wbw_dbg->debug_log("patch request for id " . $change_id);

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
            "virtual_meeting_link",
            "format_shared_id_list"
        );

        foreach ($quickedit_change as $key => $value) {
            // $wbw_dbg->debug_log("checking " . $key);
            if ((!in_array($key, $change_subfields)) || (is_array($value))) {
                // $wbw_dbg->debug_log("removing " . $key);
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
            return $this->wbw_rest_error("Submission id {$change_id} is already $change_made", 400);
        }
        // $wbw_dbg->debug_log("change made is ".$change_made);

        // get our saved changes from the db
        $saved_change = json_decode($result['changes_requested'], 1);

        // put the quickedit ones over the top

        // $wbw_dbg->debug_log("merge before - saved");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($saved_change));
        // $wbw_dbg->debug_log("merge before - quickedit");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($quickedit_change));

        $merged_change = array_merge($saved_change, $quickedit_change);

        // $wbw_dbg->debug_log("merge after - saved");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($merged_change));

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $wbw_submissions_table_name . ' set changes_requested = "%s",change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            json_encode($merged_change),
            'updated',
            $username,
            current_time('mysql', true),
            NULL,
            $request['id']
        );
        // $wbw_dbg->debug_log($wbw_dbg->vdump($sql));

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $this->wbw_rest_success('Updated submission id ' . $change_id);
    }

    public function approve_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;
        global $wbw_dbg;

        $wbw_dbg->debug_log("REQUEST");
        $wbw_dbg->debug_log($wbw_dbg->vdump($request));
        // body parameters
        $params = $request->get_json_params();
        // url parameters from parsed route
        $change_id = $request->get_param('id');

        // clean/validate supplied approval message
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->wbw_rest_error('Approve message must be less than 1024 characters', 400);
            }
        }

        // retrieve our submission id from the one specified in the route
        $wbw_dbg->debug_log("getting changes for id " . $change_id);
        $result = $this->get_submission_id_with_permission_check($change_id);
        if (is_wp_error($result)) {
            return $result;
        }

        // can't approve an already actioned submission
        $change_made = $result['change_made'];
        if (($change_made === 'approved') || ($change_made === 'rejected')) {
            return $this->wbw_rest_error("Submission id {$change_id} is already {$change_made}", 400);
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
            "virtual_meeting_link",
            "format_shared_id_list",
            "location_sub_province",
            "location_nation",
        );

        foreach ($change as $key => $value) {
            if (!in_array($key, $change_subfields)) {
                unset($change[$key]);
            }
        }

        if ($add_email === true) {
            $change['contact_email_1'] = $submitter_email;
        }

        // $wbw_dbg->debug_log("json decoded");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($change));

        // approve based on different change types
        $submission_type = $result['submission_type'];
        $wbw_dbg->debug_log("change type = " . $submission_type);
        switch ($submission_type) {
            case 'reason_new':
                // workaround for semantic new meeting bug
                $change['id_bigint'] = 0;
                // handle publish/unpublish here
                $change['published'] = 1;
                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                if (is_wp_error($response)) {
                    return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                }

                break;
            case 'reason_change':
                // needs an id_bigint not a meeting_id
                $change['id_bigint'] = $result['meeting_id'];

                $wbw_dbg->debug_log("CHANGE");
                $wbw_dbg->debug_log($wbw_dbg->vdump($change));

                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                if (is_wp_error($response)) {
                    return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                }
                $wbw_dbg->debug_log("response");
                $wbw_dbg->debug_log($wbw_dbg->vdump($response));
                $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
                $wbw_dbg->debug_log("arr");
                $wbw_dbg->debug_log($wbw_dbg->vdump($arr));
                $wbw_dbg->debug_log("change");
                $wbw_dbg->debug_log($wbw_dbg->vdump($change));
                // the response back from BMLT doesnt even match what we are trying to change
                if ((!empty($arr['id_bigint'])) && ($arr['id_bigint'] != $change['id_bigint'])) {
                    return $this->wbw_rest_error('BMLT Communication Error - Meeting change failed', 500);
                }

                break;
            case 'reason_close':

                $wbw_dbg->debug_log($wbw_dbg->vdump($params));

                // are we doing a delete or an unpublish on close?
                if ((!empty($params['delete'])) && ($params['delete'] == "true")) {
                    $changearr = array();
                    $changearr['bmlt_ajax_callback'] = 1;
                    $changearr['delete_meeting'] = $result['meeting_id'];
                    // response message {'success':true,'report':'3557'}
                    $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                    if (is_wp_error($response)) {
                        return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                    }

                    $arr = json_decode(wp_remote_retrieve_body($response), true);

                    if ((!empty($arr['success'])) && ($arr['success'] != 'true')) {
                        return $this->wbw_rest_error('BMLT Communication Error - Meeting deletion failed', 500);
                    }
                    if ((!empty($arr['report'])) && ($arr['report'] != $change['id_bigint'])) {
                        return $this->wbw_rest_error('BMLT Communication Error - Meeting deletion failed', 500);
                    }
                } else {
                    // unpublish by default
                    $change['published'] = 0;

                    $changearr = array();
                    $changearr['bmlt_ajax_callback'] = 1;
                    $changearr['set_meeting_change'] = json_encode($change);
                    $response = $this->bmlt_integration->postAuthenticatedRootServerRequest('', $changearr);

                    if (is_wp_error($response)) {
                        return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
                    }

                    $arr = json_decode(wp_remote_retrieve_body($response), true)[0];

                    if ((!empty($arr['published'])) && ($arr['published'] != 0)) {
                        return $this->wbw_rest_error('BMLT Communication Error - Meeting unpublish failed', 500);
                    }
                }

                break;
            case 'reason_other': {
                    break;
                }

            default:
                return $this->wbw_rest_error("This change type ({$submission_type}) cannot be approved", 400);
        }

        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare(
            'UPDATE ' . $wbw_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s", action_message="%s" where id="%d" limit 1',
            'approved',
            $username,
            current_time('mysql', true),
            $message,
            $request['id']
        );

        $result = $wpdb->get_results($sql, ARRAY_A);

        $from_address = get_option('wbw_email_from_address');

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
        $wbw_dbg->debug_log("Approval email");
        $wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        //
        // send fso email
        //

        if ($submission_type == "reason_new") {
            if ((!empty($change['starter_kit_required'])) && ($change['starter_kit_required'] === 'yes') && (!empty($change['starter_kit_postal_address']))) {
                $wbw_dbg->debug_log("We're sending a starter kit");
                $template = get_option('wbw_fso_email_template');
                if (!empty($template)) {
                    $subject = 'Starter Kit Request';
                    $to_address = get_option('wbw_fso_email_address');
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
                    $wbw_dbg->debug_log("FSO email");
                    $wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $wbw_dbg->vdump($headers));

                    wp_mail($to_address, $subject, $body, $headers);
                } else {
                    $wbw_dbg->debug_log("FSO email is empty");
                }
            }
        }

        return $this->wbw_rest_success('Approved submission id ' . $change_id);
    }

    private function check_bmltserver_parameters($username, $password, $server)
    {
        // global $wbw_dbg;
        // $wbw_dbg->debug_log($wbw_dbg->vdump($username));
        // $wbw_dbg->debug_log($wbw_dbg->vdump($password));
        // $wbw_dbg->debug_log($wbw_dbg->vdump($server));
        // $wbw_dbg->debug_log($wbw_dbg->vdump(empty($password)));

        if (empty($username)) {
            return $this->wbw_rest_error('Empty BMLT username parameter', 400);
        }
        if (empty($password)) {
            return $this->wbw_rest_error('Empty BMLT password parameter', 400);
        }
        if (empty($server)) {
            return $this->wbw_rest_error('Empty BMLT server parameter', 400);
        }
        if (substr($server, -1) !== '/') {
            return $this->wbw_rest_error('BMLT Server address missing trailiing /', 400);
        }
        return true;
    }

    public function get_bmltserver_handler($request)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log('get test results returning');
        $wbw_dbg->debug_log(get_option("wbw_bmlt_test_status", "failure"));

        $response = array("wbw_bmlt_test_status" => get_option("wbw_bmlt_test_status", "failure"));

        // return json_encode($response);
        return $response;
    }

    // This is for testing username/password/server combination
    public function post_bmltserver_handler($request)
    {
        global $wbw_dbg;

        $username = $request['wbw_bmlt_username'];
        $password = $request['wbw_bmlt_password'];
        $server = $request['wbw_bmlt_server_address'];

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        $wbw_dbg->debug_log('check_bmltserver_parameters returned');
        $wbw_dbg->debug_log($wbw_dbg->vdump($result));
        if ($result !== true) {
            $wbw_dbg->debug_log('update option to failure');
            $r = update_option("wbw_bmlt_test_status", "failure");
            $wbw_dbg->debug_log('update_option returned '.$r);

            // $result is a WP_Error
            $data = array(
                "status" => $result->get_error_code(),
                "wbw_bmlt_test_status" => "failure"
            );            
            $result->add_data($data, $result->get_error_code());
            $wbw_dbg->debug_log('returning');
            $wbw_dbg->debug_log($wbw_dbg->vdump($result));
    
            return $result;
        }

        $ret = $this->bmlt_integration->testServerAndAuth($username, $password, $server);
        $wbw_dbg->debug_log('testServerAndAuth returned');
        $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
        if (is_wp_error($ret)) {
            $wbw_dbg->debug_log('update option to failure');
            $r = update_option("wbw_bmlt_test_status", "failure");
            $wbw_dbg->debug_log('update_option returned '.$r);
            $response = array(
                "wbw_bmlt_test_status" => "failure"
            );       
            return $this->wbw_rest_error_with_data('Server and Authentication test failed - ' . $ret->get_error_message(), 500, $response);
        } else {
            $wbw_dbg->debug_log('update option to success');
            $r = update_option("wbw_bmlt_test_status", "success");
            $wbw_dbg->debug_log('update_option returned '.$r);
            $response = array(
                "message" => "BMLT Server and Authentication test succeeded.",
                "wbw_bmlt_test_status" => "success"
            );
            return $this->wbw_rest_success($response);
        }
    }

    public function patch_bmltserver_handler($request)
    {

        $username = $request['wbw_bmlt_username'];
        $password = $request['wbw_bmlt_password'];
        $server = $request['wbw_bmlt_server_address'];

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        if ($result !== true) {
            return $result;
        }

        update_option('wbw_bmlt_username', $username);
        update_option('wbw_bmlt_password', $password);
        update_option('wbw_bmlt_server_address', $server);
        return $this->wbw_rest_success('BMLT Server and Authentication details updated.');
    }


    private function get_emails_by_servicebody_id($id)
    {
        global $wpdb;
        global $wbw_service_bodies_access_table_name;

        $emails = array();
        $sql = $wpdb->prepare('SELECT wp_uid from ' . $wbw_service_bodies_access_table_name . ' where service_body_bigint="%d"', $id);
        $result = $wpdb->get_col($sql);
        foreach ($result as $key => $value) {
            $user = get_user_by('ID', $value);
            $emails[] = $user->user_email;
        }
        return implode(',', $emails);
    }

    private function invalid_form_field($field)
    {
        return $this->wbw_rest_error('Form field "' . $field . '" is invalid.', 400);
    }

    private function bmlt_retrieve_single_meeting($meeting_id)
    {
        global $wbw_dbg;

        $wbw_bmlt_server_address = get_option('wbw_bmlt_server_address');
        $url = $wbw_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" . $meeting_id . "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: */*",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        if (!$resp) {
            return $this->wbw_rest_error('Server error retrieving meeting list', 500);
        }
        curl_close($curl);
        $meetingarr = json_decode($resp, true);
        if (empty($meetingarr[0])) {
            return $this->wbw_rest_error('Server error retrieving meeting list', 500);
        }
        $meeting = $meetingarr[0];
        $wbw_dbg->debug_log($wbw_dbg->vdump($meeting));
        // how possibly can we get a meeting that is not the same as we asked for
        if ($meeting['id_bigint'] != $meeting_id) {
            return $this->wbw_rest_error('Server error retrieving meeting list', 500);
        }
        return $meeting;
    }

    public function meeting_update_form_handler_rest($data)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("in rest handler");
        $wbw_dbg->debug_log($wbw_dbg->vdump($data));
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
            return $this->wbw_rest_error('No valid meeting update reason provided', 400);
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
            "virtual_meeting_link" => array("url", false),
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
                return $this->wbw_rest_error('Form field "' . $field . '" is required.', 400);
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
                        break;
                    case ('time'):
                        if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:00$/', $data[$field])) {
                            return $this->invalid_form_field($field);
                        }
                        break;
                    default:
                        return $this->wbw_rest_error('Form processing error', 500);
                        break;
                }
                $sanitised_fields[$field] = $data[$field];
            }
        }

        // drop out everything that isnt in our approved list
        $data = array();

        // fields used throughout the rest of the form processing
        $reason = $sanitised_fields['update_reason'];
        $service_body_bigint = CONST_OTHER_SERVICE_BODY;
        if (!empty($sanitised_fields['service_body_bigint'])) {
            $service_body_bigint = $sanitised_fields['service_body_bigint'];
        }
        $submitter_name = $sanitised_fields['first_name'] . " " . $sanitised_fields['last_name'];
        $submitter_email = $sanitised_fields['email_address'];
        $submission = array();


        // create our submission for the database changes_requested field
        switch ($reason) {
            case ('reason_new'):
                $subject = 'New meeting notification';

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
                    "virtual_meeting_link",
                    "format_shared_id_list",
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "additional_info",
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
                $subject = 'Change meeting notification';

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
                    "virtual_meeting_link",
                    "format_shared_id_list",
                );

                $allowed_fields_extra = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "additional_info",
                );

                $bmlt_meeting = $this->bmlt_retrieve_single_meeting($sanitised_fields['meeting_id']);
                // $wbw_dbg->debug_log($wbw_dbg->vdump($meeting));
                if (is_wp_error($bmlt_meeting)) {
                    return $bmlt_meeting;
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
                        $wbw_dbg->debug_log("found a blank bmlt entry " . $field);
                        $submission[$field] = $sanitised_fields[$field];
                    }
                    // if the field is in bmlt and its different to the submitted item, add it to the list
                    else if ((!empty($bmlt_meeting[$field])) && (!empty($sanitised_fields[$field]))) {
                        if ($bmlt_meeting[$field] != $sanitised_fields[$field]) {
                            // $wbw_dbg->debug_log("{$field} is different");
                            // $wbw_dbg->debug_log("*** bmlt meeting");
                            // $wbw_dbg->debug_log($wbw_dbg->vdump($bmlt_meeting));
                            // $wbw_dbg->debug_log("*** sanitised fields");
                            // $wbw_dbg->debug_log($wbw_dbg->vdump($sanitised_fields));
                            // don't allow someone to modify a meeting service body
                            if ($field === 'service_body_bigint') {
                                return $this->wbw_rest_error('Service body cannot be changed.', 400);
                            }
                            $submission[$field] = $sanitised_fields[$field];
                        }
                    }
                }

                if (!count($submission)) {
                    return $this->wbw_rest_error('Nothing was changed.', 400);
                }

                // add in extra form fields (non BMLT fields) to the submission
                foreach ($allowed_fields_extra as $field) {
                    if (!empty($sanitised_fields[$field])) {
                        $submission[$field] = $sanitised_fields[$field];
                    }
                }

                $wbw_dbg->debug_log("SUBMISSION");
                $wbw_dbg->debug_log($wbw_dbg->vdump($submission));
                // store away the original meeting name so we know what changed
                $submission['original_meeting_name'] = $bmlt_meeting['meeting_name'];

                break;
            case ('reason_close'):
                $subject = 'Close meeting notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "additional_info",
                );

                foreach ($allowed_fields as $item) {
                    if (isset($sanitised_fields[$item])) {
                        $submission[$item] = $sanitised_fields[$item];
                    }
                }
                // populate the meeting name so we dont need to do it again on the submission page
                $meeting = $this->bmlt_retrieve_single_meeting($sanitised_fields['meeting_id']);
                $submission['meeting_name'] = $meeting['meeting_name'];

                break;
            case ('reason_other'):
                $subject = 'Other notification';

                // form fields allowed in changes_requested for this change type
                $allowed_fields = array(
                    "contact_number_confidential",
                    "group_relationship",
                    "add_email",
                    "other_reason",
                );

                foreach ($allowed_fields as $item) {
                    if (isset($sanitised_fields[$item])) {
                        $submission[$item] = $sanitised_fields[$item];
                    }
                }

                break;
            default:
                return $this->wbw_rest_error('Invalid meeting change', 400);
        }

        $wbw_dbg->debug_log("SUBMISSION");
        $wbw_dbg->debug_log($wbw_dbg->vdump($submission));



        // id mediumint(9) NOT NULL AUTO_INCREMENT,
        // submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        // change_time datetime DEFAULT '0000-00-00 00:00:00',
        // changed_by varchar(10),
        // change_made varchar(10),
        // submitter_name tinytext NOT NULL,
        // submission_type tinytext NOT NULL,
        // submitter_email varchar(320) NOT NULL,

        // insert into submissions db
        global $wpdb;
        global $wbw_submissions_table_name;

        $wpdb->insert(
            $wbw_submissions_table_name,
            array(
                'submission_time'   => current_time('mysql', true),
                'meeting_id' => $sanitised_fields['meeting_id'],
                'submitter_name' => $submitter_name,
                'submission_type'  => $reason,
                'submitter_email' => $submitter_email,
                'changes_requested' => wp_json_encode($submission, 0, 1),
                'service_body_bigint' => $service_body_bigint
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
        // $wbw_dbg->debug_log("id = " . $insert_id);
        $message = array(
            "message" => 'Form submission successful, submission id ' . $insert_id,
            "form_html" => '<h3>Form submission successful, your submission id  is #' . $insert_id . '. You will also receive an email confirmation of your submission.</h3>'
        );

        // Send our emails out

        // Common email fields
        $from_address = get_option('wbw_email_from_address');


        // Send a notification to the trusted servants
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

        $to_address = $this->get_emails_by_servicebody_id($service_body_bigint);
        $subject = '[bmlt-workflow] ' . $submission_type . 'request received - ID ' . $insert_id;
        $body = 'Log in to <a href="' . get_site_url() . '/wp-admin/admin.php?page=wbw-submissions">WBW Submissions Page</a> to review.';
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        wp_mail($to_address, $subject, $body, $headers);


        // Send email to the submitter
        $to_address = $submitter_email;
        $subject = "NA Meeting Change Request Acknowledgement - Submission ID " . $insert_id;

        $template = get_option('wbw_submitter_email_template');

        $subfield = '{field:submission}';
        $subwith = $this->submission_format($submission);
        $template = str_replace($subfield, $subwith, $template);

        $body = $template;

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_address);
        $wbw_dbg->debug_log("to:" . $to_address . " subject:" . $subject . " body:" . $body . " headers:" . $wbw_dbg->vdump($headers));
        wp_mail($to_address, $subject, $body, $headers);

        return $this->wbw_rest_success($message);
        // return;
    }

    private function submission_format($submission)
    {

        $bmlt = new Integration;
        $formats = $bmlt->getMeetingFormats();

        $table = '';

        foreach ($submission as $key => $value) {
            switch ($key) {
                case "start_time":
                    $table .= '<tr><td>Start Time:</td><td>' . $value . '</td></tr>';
                    break;
                case "duration":
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

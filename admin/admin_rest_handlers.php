<?php

use Crell\ApiProblem\ApiProblem;

if (!defined('ABSPATH')) exit; // die if being called directly

class wbw_submissions_rest_handlers
{

    public function __construct()
    {
        $this->bmlt_integration = new BMLTIntegration;
    }

    // accepts raw string or array
    function wbw_rest_success($message)
    {
        if(is_array($message))
        {
            $data = $message;
        }
        else
        {
            $data = array('message'=> $message);
        }
        $response = new WP_REST_Response();
        $response->set_data($data);
        $response->set_status(200);
        return $response;
    }

    private function wbw_rest_error($message, $code)
    {
        return new WP_Error('wbw_error', $message, array('status' => $code));
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
        // error_log($sql);
        $result = $wpdb->get_results($sql, ARRAY_A);
        // error_log(vdump($result));
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

        $params = $request->get_params();
        error_log(vdump($params));
        // error_log("params detail".$params['detail']);
        // only an admin can get the service areas detail (permissions) information
        if ((!empty($params['detail'])) && ($params['detail'] == "true") && (current_user_can('manage_options'))) {
            // do detail lookup

            $sblist = array();
    
            $req = array();
            $req['admin_action'] = 'get_service_body_info';
            $req['flat'] = '';
            $bmlt_integration = new BMLTIntegration;
    
            // get an xml for a workaround
            $response = $bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
            if (is_wp_error($response)) {
                return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
            }
    
            $xml = simplexml_load_string($response['body']);
            $arr = json_decode(json_encode($xml), 1);
    
            // error_log(vdump($arr));
    
            $idlist = array();
    
            // make our list of service bodies
            foreach ($arr['service_body'] as $key => $value) {
                // error_log("looping key = " . $key);
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
            // error_log("get ids");
            $sqlresult = $wpdb->get_col('SELECT service_body_bigint FROM ' . $wbw_service_bodies_table_name . ';', 0);
    
            // error_log(vdump($sqlresult));
            $missing = array_diff($idlist, $sqlresult);
            // error_log("missing ids");
            // error_log(vdump($missing));
    
            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s", service_body_bigint="%d", show_on_form=0', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }
            // update any values that may have changed since last time we looked
    
            foreach ($idlist as $value) {
                $sql = $wpdb->prepare('UPDATE ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s" where service_body_bigint="%d"', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }
    
            // error_log("our sblist");
            // error_log(vdump($sblist));
    
            // make our group membership lists
            foreach ($sblist as $key => $value) {
                error_log("getting memberships for " . $key);
                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $wbw_service_bodies_access_table_name . ' where service_body_bigint = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                // error_log(vdump($result));
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
            // error_log("simple list of service areas and names");
            $result = $wpdb->get_results('SELECT * from ' . $wbw_service_bodies_table_name . ' where show_on_form != "0"', ARRAY_A);
            // error_log(vdump($result));
            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value) {
                $sblist[$value['service_body_bigint']]['name'] = $value['service_area_name'];
            }
            // error_log(vdump($sblist));

        }

        return $sblist;

    }

    public function post_service_bodies_handler($request)
    {
        global $wpdb;
        global $wbw_service_bodies_access_table_name;
        global $wbw_capability_manage_submissions;
        global $wbw_service_bodies_table_name;

        // error_log("request body");
        // error_log(vdump($request->get_json_params()));
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
        // error_log(vdump($sql));
        // error_log(vdump($result));
        foreach ($users as $user) {
            error_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($wbw_capability_manage_submissions);
                // error_log("adding cap");
            } else {
                $user->remove_cap($wbw_capability_manage_submissions);
                // error_log("removing cap");
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

        $this_user = wp_get_current_user();
        $current_uid = $this_user->get('ID');
        $sql = $wpdb->prepare('SELECT * FROM ' . $wbw_submissions_table_name . ' s inner join ' . $wbw_service_bodies_access_table_name . ' a on s.service_body_bigint = a.service_body_bigint where a.wp_uid =%d and s.id="%d" limit 1', $current_uid, $change_id);
        // error_log($sql);
        $result = $wpdb->get_row($sql, ARRAY_A);
        if (empty($result)) {
            return $this->wbw_rest_error("Permission denied viewing submission id {$change_id}", 400);
        }
        return $result;
    }

    public function reject_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;

        $change_id = $request->get_param('id');

        error_log("rejection request for id " . $change_id);

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

        $params = $request->get_json_params();
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->wbw_rest_error('Reject message must be less than 1024 characters', 400);
            }
        } else {
            error_log("action message is null");
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

        return $this->wbw_rest_success('Rejected submission id ' . $change_id);
    }

    public function patch_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;

        $change_id = $request->get_param('id');

        error_log("patch request for id " . $change_id);

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
            // error_log("checking " . $key);
            if ((!in_array($key, $change_subfields)) || (is_array($value))) {
                // error_log("removing " . $key);
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
        // error_log("change made is ".$change_made);

        // get our saved changes from the db
        $saved_change = json_decode($result['changes_requested'], 1);

        // put the quickedit ones over the top

        // error_log("merge before - saved");
        // error_log(vdump($saved_change));
        // error_log("merge before - quickedit");
        // error_log(vdump($quickedit_change));

        $merged_change = array_merge($saved_change, $quickedit_change);

        // error_log("merge after - saved");
        // error_log(vdump($merged_change));

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
        // error_log(vdump($sql));

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $this->wbw_rest_success('Updated submission id ' . $change_id);
    }

    public function approve_submission_handler($request)
    {
        global $wpdb;
        global $wbw_submissions_table_name;

        $change_id = $request->get_param('id');

        error_log("getting changes for id " . $change_id);

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

        $submission_type = $result['submission_type'];

        $change = json_decode($result['changes_requested'], 1);

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
            "format_shared_id_list"
        );

        foreach ($change as $key => $value) {
            if (!in_array($key, $change_subfields)) {
                unset($change[$key]);
            }
        }

        // error_log("json decoded");
        // error_log(vdump($change));
        // error_log("change type = " . $submission_type);
        switch ($submission_type) {
            case 'reason_new':
                // $change['admin_action'] = 'add_meeting';
                // workaround for new meeting bug
                $change['id_bigint'] = 0;
                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postConfiguredRootServerRequest('', $changearr);
                break;
            case 'reason_change':
                // needs an id_bigint not a meeting_id
                $change['id_bigint'] = $change['meeting_id'];
                unset($change['meeting_id']);
                $changearr = array();
                $changearr['bmlt_ajax_callback'] = 1;
                $changearr['set_meeting_change'] = json_encode($change);
                $response = $this->bmlt_integration->postConfiguredRootServerRequest('', $changearr);

                // $change['admin_action'] = 'modify_meeting';
                // $response = $this->bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/json.php', $change);
                break;
            default:
                return $this->wbw_rest_error("This change type ({$submission_type}) cannot be approved", 400);
        }

        if (is_wp_error($response)) {
            return $this->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
        }

        $params = $request->get_json_params();
        // error_log($params);
        $message = '';
        if (!empty($params['action_message'])) {
            $message = $params['action_message'];
            if (strlen($message) > 1023) {
                return $this->wbw_rest_error('Approve message must be less than 1024 characters', 400);
            }
        } else {
            error_log("action message is null");
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

        //
        // send action email
        //

        return $this->wbw_rest_success('Approved submission id ' . $change_id);
    }

    public function post_server_handler($request)
    {
        $username = $request['wbw_bmlt_username'];
        $password = $request['wbw_bmlt_password'];
        $server = $request['wbw_bmlt_server_address'];

        $ret = $this->bmlt_integration->testServerAndAuth($username, $password, $server);
        error_log(vdump($ret));
        if (is_wp_error($ret)) {
            return $this->wbw_rest_error('Server and Authentication test failed - ' . $ret->get_error_message(), 500);
        } else {
            return $this->wbw_rest_success('Server and Authentication test succeeded.');
        }
    }
}

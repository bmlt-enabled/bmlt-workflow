<?php

if (!defined('ABSPATH')) exit; // die if being called directly

class bmaw_submissions_rest_handlers
{

    public function get_submissions_handler()
    {

        global $wpdb;
        global $bmaw_submissions_table_name;

        $result = $wpdb->get_results('SELECT * FROM ' . $bmaw_submissions_table_name, ARRAY_A);
        $myrequested = $result['changes_requested'];
        $result['changes_requested'] = json_decode($myrequested,true,1);
        error_log("this is our changes requested array");
        error_log(vdump($result));
        return $result;
    }

    public function get_service_areas_handler()
    {
        // call bmlt for service area list
        // add list of wp uids with access
        // return as array of all service areas

        global $wpdb;
        global $bmaw_service_areas_table_name;
        global $bmaw_service_areas_access_table_name;

        $sblist = array();

        // only admins can see/modify the permissions list
        if (current_user_can('manage_options')) {

            $req = array();
            $req['admin_action'] = 'get_service_body_info';
            $req['flat'] = '';
            $bmlt_integration = new BMLTIntegration;

            // get an xml for a workaround
            $response = $bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
            if (is_wp_error($response)) {
                wp_die("BMLT Configuration Error - Unable to retrieve meeting formats");
            }

            $xml = simplexml_load_string($response['body']);
            $arr = json_decode(json_encode($xml), 1);

            error_log(vdump($arr));

            $idlist = array();

            // make our list of service bodies
            foreach ($arr['service_body'] as $key => $value) {
                // error_log("looping key = " . $key);
                if (array_key_exists('@attributes', $value)) {
                    $sbid = $value['@attributes']['id'];
                    $idlist[] = $sbid;
                    $sblist[$sbid] = array('name' => $value['@attributes']['name']);
                }
                else
                {
                    // we need a name at minimum
                    break;
                }
                $sblist[$sbid]['contact_email']='';
                if (array_key_exists('contact_email', $value))
                {
                    $sblist[$sbid]['contact_email'] = $value['contact_email'];
                }
            }

            // update our service area list in the database in case there have been some new ones added
            error_log("get ids");
            $sqlresult = $wpdb->get_col('SELECT service_area_id FROM ' . $bmaw_service_areas_table_name . ';', 0);

            error_log(vdump($sqlresult));
            $missing = array_diff($idlist, $sqlresult);
            error_log("missing ids");
            error_log(vdump($missing));

            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $bmaw_service_areas_table_name . ' set contact_email="%s", service_area_name="%s", service_area_id="%d", show_on_form=0', $sblist[$value]['contact_email'], $sblist[$value]['name'],$value);
                $wpdb->query($sql);
            }
            // update any values that may have changed since last time we looked

            foreach ($idlist as $value)
            {
                $sql = $wpdb->prepare('UPDATE '. $bmaw_service_areas_table_name . ' set contact_email="%s", service_area_name="%s" where service_area_id="%d"', $sblist[$value]['contact_email'], $sblist[$value]['name'],$value);
                $wpdb->query($sql);
            }

            error_log("our sblist");
            error_log(vdump($sblist));

            // make our group membership lists
            foreach ($sblist as $key => $value) {
                error_log("getting memberships for " . $key);
                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $bmaw_service_areas_access_table_name . ' where service_area_id = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                error_log(vdump($result));
                $sblist[$key]['membership'] = implode(',', $result);
            }
            // get the form display settings
            $sqlresult = $wpdb->get_results('SELECT service_area_id,show_on_form FROM ' . $bmaw_service_areas_table_name, ARRAY_A);

            foreach ($sqlresult as $key => $value)
            {
                $bool = $value['show_on_form']?(true):(false);
                $sblist[$value['service_area_id']]['show_on_form']=$bool;
            }
        }
        else
        {
            // error_log("simple list of service areas and names");
            $result = $wpdb->get_results('SELECT * from ' . $bmaw_service_areas_table_name . ' where show_on_form != "0"',ARRAY_A);
            // error_log(vdump($result));
            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value)
            {
                $sblist[$value['service_area_id']]['name']=$value['service_area_name'];
            }
            // error_log(vdump($sblist));
        }

        return $sblist;
    }

    public function post_service_areas($request)
    {
        global $wpdb;
        global $bmaw_service_areas_access_table_name;
        global $bmaw_capability_manage_submissions;
        global $bmaw_service_areas_table_name;

        // error_log("request body");
        // error_log(vdump($request->get_json_params()));
        $permissions = $request->get_json_params();
        // clear out our old permissions
        $wpdb->query('DELETE from ' . $bmaw_service_areas_access_table_name);
        // insert new permissions from form
        foreach ($permissions as $sb => $arr) {
            $members = $arr['membership'];
            foreach ($members as $member) {
                $sql = $wpdb->prepare('INSERT into ' . $bmaw_service_areas_access_table_name . ' SET wp_uid = "%d", service_area_id="%d"', $member, $sb);
                $wpdb->query($sql);
            }
            // update show/hide
            $show_on_form = $arr['show_on_form'];
            $sql = $wpdb->prepare('UPDATE ' . $bmaw_service_areas_table_name . ' SET show_on_form = "%d" where service_area_id="%d"', $show_on_form, $sb);
            $wpdb->query($sql);
        }

        // add / remove user capabilities
        $users = get_users();
        $result = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $bmaw_service_areas_access_table_name, 0);
        // error_log(vdump($sql));
        // $result = $wpdb->get_results($sql, ARRAY_N);
        error_log(vdump($result));
        foreach ($users as $user) {
            error_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($bmaw_capability_manage_submissions);
                error_log("adding cap");
            } else {
                $user->remove_cap($bmaw_capability_manage_submissions);
                error_log("removing cap");
            }
        }
        $resp = "ok";
        return $resp;
    }

    public function delete_submission_handler($request)
    {

        global $wpdb;
        global $bmaw_submissions_table_name;
        $sql = $wpdb->prepare('DELETE FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        // Return all of our comment response data.
        return $result;
    }

    public function get_submission_handler($request)
    {
        global $wpdb;
        global $bmaw_submissions_table_name;
        $sql = $wpdb->prepare('SELECT * FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        // Return all of our comment response data.
        return $result;
    }

    public function approve_submission_handler($request)
    {
        $change_id = $request->get_param('id');

        error_log("getting changes for id " . $change_id);

        global $wpdb;
        global $bmaw_submissions_table_name;

        $sql = $wpdb->prepare('SELECT change_made FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);
        if ($result[0]['change_made'] === 'Approved') {
            return "{'response':'already approved'}";
        }

        $sql = $wpdb->prepare('SELECT changes_requested FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);
        if ($result) {
            error_log(vdump($result));
        } else {
            error_log("no result found");
        }
        $change = json_decode($result[0]['changes_requested']);
        error_log("json decoded");
        error_log(vdump($change));
        $change['admin_action'] = 'modify_meeting';

        $response = $this->bmlt_integration->postConfiguredRootServerRequestSemantic('local_server/server_admin/json.php', $change);
        // ERROR HANDLING NEEDED
        // if( is_wp_error( $response ) ) {
        // 	wp_die("BMLT Configuration Error - Unable to retrieve meeting formats");
        // }
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        $sql = $wpdb->prepare('UPDATE ' . $bmaw_submissions_table_name . ' set change_made = "%s", changed_by = "%s", change_time = "%s" where id="%d" limit 1', 'Approved', $username, current_time('mysql', true), $request['id']);
        $result = $wpdb->get_results($sql, ARRAY_A);

        return "{'response':'approved'}";
    }
}

<?php 
namespace wbw\REST\Handlers;

use wbw\BMLT\Integration;
use wbw\REST\HandlerCore;

class ServiceBodiesHandler
{

    public function __construct($stub = null)
    {
        if (empty($stub))
        {
            $this->bmlt_integration = new Integration;
        }
        else
        {
            $this->bmlt_integration = $stub;
        }
        $this->handlerCore = new HandlerCore;
    }

    public function get_service_bodies_handler($request)
    {

        global $wpdb;
        global $wbw_service_bodies_table_name;
        global $wbw_service_bodies_access_table_name;
        global $wbw_dbg;

        $params = $request->get_params();
        $wbw_dbg->debug_log($wbw_dbg->vdump($params));
        // only an admin can get the service bodies detail (permissions) information
        if ((!empty($params['detail'])) && ($params['detail'] == "true") && (current_user_can('manage_options'))) {
            // detail list
            $sblist = array();

            $req = array();
            $req['admin_action'] = 'get_service_body_info';
            $req['flat'] = '';

            // get an xml for a workaround
            $response = $this->bmlt_integration->postAuthenticatedRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
            if (is_wp_error($response)) {
                return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
            }

            $xml = simplexml_load_string($response['body']);
            $arr = json_decode(json_encode($xml), 1);

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

            // update our service body list in the database in case there have been some new ones added
            $sqlresult = $wpdb->get_col('SELECT service_body_bigint FROM ' . $wbw_service_bodies_table_name . ';', 0);

            $missing = array_diff($idlist, $sqlresult);

            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s", service_body_bigint="%d", show_on_form=0', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }
            // make sure our 'Other' service body has an entry
            $sql = $wpdb->prepare('INSERT IGNORE into ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s", service_body_bigint="%d", show_on_form=1', '', 'Other', CONST_OTHER_SERVICE_BODY);
            $wpdb->query($sql);

            // update any values that may have changed since last time we looked

            foreach ($idlist as $value) {
                $sql = $wpdb->prepare('UPDATE ' . $wbw_service_bodies_table_name . ' set contact_email="%s", service_area_name="%s" where service_body_bigint="%d"', $sblist[$value]['contact_email'], $sblist[$value]['name'], $value);
                $wpdb->query($sql);
            }

            // make our group membership lists
            foreach ($sblist as $key => $value) {
                $wbw_dbg->debug_log("getting memberships for " . $key);
                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $wbw_service_bodies_access_table_name . ' where service_body_bigint = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                $sblist[$key]['membership'] = implode(',', $result);
            }
            // get the form display settings
            $sqlresult = $wpdb->get_results('SELECT service_body_bigint,show_on_form FROM ' . $wbw_service_bodies_table_name, ARRAY_A);

            foreach ($sqlresult as $key => $value) {
                $bool = $value['show_on_form'] ? (true) : (false);
                $sblist[$value['service_body_bigint']]['show_on_form'] = $bool;
            }
        } else {
            // simple list
            $sblist = array();
            // get all the service bodies, except our 'Other' which is not required as part of any client side BMLT lookups
            $sql = $wpdb->prepare('SELECT * from ' . $wbw_service_bodies_table_name . ' where show_on_form != "0" and service_body_bigint != "%d"', CONST_OTHER_SERVICE_BODY);
            $result = $wpdb->get_results($sql);
            // $result = $wpdb->get_results('SELECT * from ' . $wbw_service_bodies_table_name . ' where show_on_form != "0"', ARRAY_A);
            $wbw_dbg->debug_log($wbw_dbg->vdump($result));
            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value) {
                $sblist[$value['service_body_bigint']]['name'] = $value['service_area_name'];
            }

        }
        return $this->handlerCore->wbw_rest_success($sblist);

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

        return $this->handlerCore->wbw_rest_success('Updated Service Bodies');
    }

}
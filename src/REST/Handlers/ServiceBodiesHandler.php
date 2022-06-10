<?php 
namespace wbw\REST\Handlers;

use wbw\BMLT\Integration;
use wbw\REST\HandlerCore;
use wbw\WBW_Database;
use wbw\WBW_Debug;
use wbw\WBW_WP_Options;

class ServiceBodiesHandler
{

    public function __construct($stub = null)
    {
        if (empty($stub))
        {
            $this->bmlt_integration = new Integration();
        }
        else
        {
            $this->bmlt_integration = $stub;
        }
        $this->handlerCore = new HandlerCore();
		$this->wbw_dbg = new WBW_Debug();
        $this->WBW_Database = new WBW_Database();
        $this->WBW_WP_Options = new WBW_WP_Options();
    }

    public function get_service_bodies_handler($request)
    {

        global $wpdb;
        

        $params = $request->get_params();
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($params));
        // only an admin can get the service bodies detail (permissions) information
        if ((!empty($params['detail'])) && ($params['detail'] == "true") && (current_user_can('manage_options'))) {
            // detail list
            $sblist = array();

            $req = array();
            $req['admin_action'] = 'get_service_body_info';
            $req['flat'] = '';

            $response = $this->bmlt_integration->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServiceBodies', $req);
            if (is_wp_error($response)) {
                return $this->handlerCore->wbw_rest_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
            }

            $arr = json_decode($response['body'], 1);

            $idlist = array();
            $this->wbw_dbg->debug_log("SERVICE BODY JSON");            
            $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($arr));

            // make our list of service bodies
            foreach ($arr as $key => $value) {
                // $wbw_dbg->debug_log("looping key = " . $key);
                if ((!empty($value['id']))&&(!empty($value['name']))) {
                    $sbid = $value['id'];
                    $idlist[] = $sbid;
                    $sblist[$sbid] = array('name' => $value['name'], 'contact_email' => '', 'description' => '');
                    if (!empty($value['description'])) {
                        $sblist[$sbid]['description'] = $value['description'];
                    }
                } else {
                    // we need a name and id at minimum
                    break;
                }
            }


            // update our service body list in the database in case there have been some new ones added
            $sqlresult = $wpdb->get_col('SELECT service_body_bigint FROM ' . $this->WBW_Database->wbw_service_bodies_table_name . ';', 0);

            $missing = array_diff($idlist, $sqlresult);

            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $this->WBW_Database->wbw_service_bodies_table_name . ' set service_body_name="%s", service_body_description="%s", service_body_bigint="%d", show_on_form=0', $sblist[$value]['name'], $sblist[$value]['description'],$value);
                $wpdb->query($sql);
            }
            // update any values that may have changed since last time we looked
            foreach ($idlist as $value) {
                $sql = $wpdb->prepare('UPDATE ' . $this->WBW_Database->wbw_service_bodies_table_name . ' set service_body_name="%s", service_body_description="%s" where service_body_bigint="%d"', $sblist[$value]['name'], $sblist[$value]['description'], $value);
                $wpdb->query($sql);
            }

            
            // make our group membership lists
            foreach ($sblist as $key => $value) {
                $this->wbw_dbg->debug_log("getting memberships for " . $key);
                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $this->WBW_Database->wbw_service_bodies_access_table_name . ' where service_body_bigint = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                $sblist[$key]['membership'] = implode(',', $result);
            }
            // get the form display settings
            $sqlresult = $wpdb->get_results('SELECT service_body_bigint,show_on_form FROM ' . $this->WBW_Database->wbw_service_bodies_table_name, ARRAY_A);

            foreach ($sqlresult as $key => $value) {
                $bool = $value['show_on_form'] ? (true) : (false);
                $sblist[$value['service_body_bigint']]['show_on_form'] = $bool;
            }
        } else {
            // simple list
            $sblist = array();
            $result = $wpdb->get_results('SELECT * from ' . $this->WBW_Database->wbw_service_bodies_table_name . ' where show_on_form != "0"', ARRAY_A);
            $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($result));
            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value) {
                $sblist[$value['service_body_bigint']]['name'] = $value['service_body_name'];
            }
        }
        return $this->handlerCore->wbw_rest_success($sblist);

    }

    public function post_service_bodies_handler($request)
    {
        global $wpdb;
        

        $this->wbw_dbg->debug_log("request body");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request->get_json_params()));
        $permissions = $request->get_json_params();
        // clear out our old permissions
        $wpdb->query('DELETE from ' . $this->WBW_Database->wbw_service_bodies_access_table_name);
        // insert new permissions from form
        if(!is_array($permissions))
        {
            $this->wbw_dbg->debug_log("error not array");

            return $this->handlerCore->wbw_rest_error('Invalid service bodies post',422);
        }
        foreach ($permissions as $sb => $arr) {
            if((empty($arr['membership']))||(empty($arr['show_on_form'])))
            {
                $this->wbw_dbg->debug_log($sb . "error empty body");

                return $this->handlerCore->wbw_rest_error('Invalid service bodies post',422);
            }
            $members = $arr['membership'];
            foreach ($members as $member) {
                $sql = $wpdb->prepare('INSERT into ' . $this->WBW_Database->wbw_service_bodies_access_table_name . ' SET wp_uid = "%d", service_body_bigint="%d"', $member, $sb);
                $wpdb->query($sql);
            }
            // update show/hide
            $show_on_form = $arr['show_on_form'];
            $sql = $wpdb->prepare('UPDATE ' . $this->WBW_Database->wbw_service_bodies_table_name . ' SET show_on_form = "%d" where service_body_bigint="%d"', $show_on_form, $sb);
            $wpdb->query($sql);
        }

        // add / remove user capabilities
        $users = get_users();
        $result = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $this->WBW_Database->wbw_service_bodies_access_table_name, 0);
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($sql));
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($result));
        foreach ($users as $user) {
            $this->wbw_dbg->debug_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
                $this->wbw_dbg->debug_log("adding cap");
            } else {
                $user->remove_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
                $this->wbw_dbg->debug_log("removing cap");
            }
        }

        return $this->handlerCore->wbw_rest_success('Updated Service Bodies');
    }

    
    public function delete_service_bodies_handler($request)
    {
        global $wbw_dbg;
        global $wpdb;
        global $wbw_service_bodies_access_table_name;
        global $wbw_service_bodies_table_name;
        global $wbw_submissions_table_name;

        $params = $request->get_params();
        $wbw_dbg->debug_log($wbw_dbg->vdump($params));
        // only an admin can get the service bodies detail (permissions) information
        if ((!empty($params['checked'])) && ($params['checked'] == "true") && (current_user_can('manage_options'))) {
            $result = $wpdb->query('DELETE from ' . $wbw_submissions_table_name);
            $wbw_dbg->debug_log("Delete submissions");
            $wbw_dbg->debug_log($wbw_dbg->vdump($result));
            $result = $wpdb->query('DELETE from ' . $wbw_service_bodies_access_table_name);
            $wbw_dbg->debug_log("Delete service bodies access");
            $wbw_dbg->debug_log($wbw_dbg->vdump($result));
            $result = $wpdb->query('DELETE from ' . $wbw_service_bodies_table_name);
            $wbw_dbg->debug_log("Delete service bodies");
            $wbw_dbg->debug_log($wbw_dbg->vdump($result));
            return $this->handlerCore->wbw_rest_success('Deleted Service Bodies');
        }
        else
        {
            return $this->handlerCore->wbw_rest_success('Nothing was performed');
        }

    }


            
}
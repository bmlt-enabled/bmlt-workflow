<?php 
namespace wbw\REST\Handlers;

use wbw\BMLT\Integration;
use wbw\REST\HandlerCore;
use wbw\WBW_Debug;
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
                // $this->wbw_dbg->debug_log("looping key = " . $key);
                if (!empty($value['@attributes'])) {
                    $sbid = $value['@attributes']['id'];
                    $idlist[] = $sbid;
                    $sblist[$sbid] = array('name' => $value['@attributes']['name'], 'description' => '');
                    if (!empty($value['description'])) {
                        $sblist[$sbid]['description'] = $value['description'];
                    }
                } else {
                    // we need a name at minimum
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
        

        // $this->wbw_dbg->debug_log("request body");
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request->get_json_params()));
        $permissions = $request->get_json_params();
        // clear out our old permissions
        $wpdb->query('DELETE from ' . $this->WBW_Database->wbw_service_bodies_access_table_name);
        // insert new permissions from form
        foreach ($permissions as $sb => $arr) {
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
                $user->add_cap($this->WBW_Database->wbw_capability_manage_submissions);
                // $this->wbw_dbg->debug_log("adding cap");
            } else {
                $user->remove_cap($$this->WBW_Database->bw_capability_manage_submissions);
                // $this->wbw_dbg->debug_log("removing cap");
            }
        }

        return $this->handlerCore->wbw_rest_success('Updated Service Bodies');
    }

}
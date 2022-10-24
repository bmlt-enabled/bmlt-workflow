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
use bmltwf\REST\HandlerCore;
use bmltwf\BMLTWF_Database;
use bmltwf\BMLTWF_WP_Options;

class ServiceBodiesHandler
{
    use \bmltwf\BMLTWF_Debug;

    public function __construct($intstub = null, $optstub = null)
    {
        if (empty($intstub)) {
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }

        if (empty($optstub)) {
            $this->BMLTWF_WP_Options = new BMLTWF_WP_Options();
        } else {
            $this->BMLTWF_WP_Options = $optstub;
        }

        $this->handlerCore = new HandlerCore();
        $this->BMLTWF_Database = new BMLTWF_Database();
    }

    public function get_service_bodies_handler($request)
    {

        global $wpdb;

        $params = $request->get_params();
        $this->debug_log(($params));
        // only an admin can get the service bodies detail (permissions) information
        if ((!empty($params['detail'])) && ($params['detail'] == "true") && (current_user_can('manage_options'))) {
            // detail list
            $sblist = array();

            $sblist = $this->bmlt_integration->getServiceBodies();
            if(\is_wp_error(($sblist)))
            {
                return $sblist;
            }

            // make a list of ids from the response
            $idlist = array();
            foreach ($sblist as $key => $value) {
                $idlist[]=$key;
            }

            // update our service body list in the database in case there have been some new ones added
            $sqlresult = $wpdb->get_col('SELECT service_body_bigint FROM ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name . ';', 0);

            $missing = array_diff($idlist, $sqlresult);

            foreach ($missing as $value) {
                $sql = $wpdb->prepare('INSERT into ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name . ' set service_body_name="%s", service_body_description="%s", service_body_bigint="%d", show_on_form=0', $sblist[$value]['name'], $sblist[$value]['description'], $value);
                $wpdb->query($sql);
            }

            // update any values that may have changed since last time we looked
            foreach ($idlist as $value) {
                $sql = $wpdb->prepare('UPDATE ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name . ' set service_body_name="%s", service_body_description="%s" where service_body_bigint="%d"', $sblist[$value]['name'], $sblist[$value]['description'], $value);
                $wpdb->query($sql);
            }

            // make our group membership lists
            foreach ($sblist as $key => $value) {
                $this->debug_log("getting memberships for " . $key);

                $sql = $wpdb->prepare('SELECT DISTINCT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' where service_body_bigint = "%d"', $key);
                $result = $wpdb->get_col($sql, 0);
                $sblist[$key]['membership'] = implode(',', $result);
            }

            // get the form display settings
            $sqlresult = $wpdb->get_results('SELECT service_body_bigint,show_on_form FROM ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name, ARRAY_A);

            foreach ($sqlresult as $key => $value) {
                $is_editable = $editable[$value['service_body_bigint']] ?? false;
                if ($is_editable) {
                    $bool = $value['show_on_form'] ? (true) : (false);
                    $sblist[$value['service_body_bigint']]['show_on_form'] = $bool;
                }
            }
        } else {
            // simple list
            $sblist = array();
            $result = $wpdb->get_results('SELECT * from ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name . ' where show_on_form != "0"', ARRAY_A);

            // create simple service area list (names of service areas that are enabled by admin with show_on_form)
            foreach ($result as $key => $value) {
                $sblist[$value['service_body_bigint']]['name'] = $value['service_body_name'];
            }
        }
        return $this->handlerCore->bmltwf_rest_success($sblist);
    }

    public function post_service_bodies_handler($request)
    {
        global $wpdb;

        $this->debug_log("request body");
        $this->debug_log(($request->get_json_params()));
        $permissions = $request->get_json_params();
        // clear out our old permissions
        $wpdb->query('DELETE from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name);
        // insert new permissions from form
        if (!is_array($permissions)) {
            $this->debug_log("error not array");

            return $this->handlerCore->bmltwf_rest_error('Invalid service bodies post', 422);
        }
        foreach ($permissions as $sb => $arr) {
            if ((!is_array($arr)) || (!array_key_exists('membership', $arr)) || (!array_key_exists('show_on_form', $arr))) {
                // if(empty($arr['membership']))
                // {
                //     $this->debug_log($sb . " error membership");

                // }
                // if(empty($arr['show_on_form']))
                // {
                //     $this->debug_log($sb . " error show_on_form");
                // }

                return $this->handlerCore->bmltwf_rest_error('Invalid service bodies post', 422);
            }
            $members = $arr['membership'];
            foreach ($members as $member) {
                $sql = $wpdb->prepare('INSERT into ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name . ' SET wp_uid = "%d", service_body_bigint="%d"', $member, $sb);
                $wpdb->query($sql);
            }
            // update show/hide
            $show_on_form = $arr['show_on_form'];
            $sql = $wpdb->prepare('UPDATE ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name . ' SET show_on_form = "%d" where service_body_bigint="%d"', $show_on_form, $sb);
            $wpdb->query($sql);
        }

        // add / remove user capabilities
        $users = get_users();
        $result = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name, 0);
        // $this->debug_log(($sql));
        // $this->debug_log(($result));
        foreach ($users as $user) {
            $this->debug_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
                $this->debug_log("adding cap");
            } else {
                $user->remove_cap($this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
                $this->debug_log("removing cap");
            }
        }

        return $this->handlerCore->bmltwf_rest_success('Updated Service Bodies');
    }


    public function delete_service_bodies_handler($request)
    {
        global $wpdb;

        $params = $request->get_params();
        $this->debug_log(($params));
        // only an admin can get the service bodies detail (permissions) information
        if ((!empty($params['checked'])) && ($params['checked'] == "true") && (current_user_can('manage_options'))) {
            $result = $wpdb->query('DELETE from ' . $this->BMLTWF_Database->bmltwf_submissions_table_name);
            $this->debug_log("Delete submissions");
            $this->debug_log(($result));
            $result = $wpdb->query('DELETE from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name);
            $this->debug_log("Delete service bodies access");
            $this->debug_log(($result));
            $result = $wpdb->query('DELETE from ' . $this->BMLTWF_Database->bmltwf_service_bodies_table_name);
            $this->debug_log("Delete service bodies");
            $this->debug_log(($result));
            return $this->handlerCore->bmltwf_rest_success('Deleted Service Bodies');
        } else {
            return $this->handlerCore->bmltwf_rest_success('Nothing was performed');
        }
    }
}

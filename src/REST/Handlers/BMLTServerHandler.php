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
use bmltwf\BMLTWF_WP_Options;

class BMLTServerHandler
{

    use \bmltwf\BMLTWF_Debug;

    public function __construct($intstub = null, $wpoptionssstub = null)
    {
        if (empty($intstub)) {
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }

        if (empty($wpoptionssstub)) {
            $this->BMLTWF_WP_Options = new BMLTWF_WP_Options();
        } else {
            $this->BMLTWF_WP_Options = $wpoptionssstub;
        }

        $this->handlerCore = new HandlerCore();
    }

    private function check_bmltserver_parameters($username, $password, $server)
    {
        // 
        // $this->debug_log(($username));
        // $this->debug_log(($password));
        // $this->debug_log(($server));
        // $this->debug_log((empty($password)));

        if (empty($username)) {
            return $this->handlerCore->bmltwf_rest_error('Empty BMLT username parameter', 422);
        }
        if (empty($password)) {
            return $this->handlerCore->bmltwf_rest_error('Empty BMLT password parameter', 422);
        }
        if (empty($server)) {
            return $this->handlerCore->bmltwf_rest_error('Empty BMLT server parameter', 422);
        }
        if (substr($server, -1) !== '/') {
            return $this->handlerCore->bmltwf_rest_error('BMLT Server address missing trailiing /', 422);
        }

        return true;
    }

    public function get_bmltserver_handler($request)
    {

        $this->debug_log('get test results returning');
        $this->debug_log(get_option("bmltwf_bmlt_test_status", "failure"));

        $response = array("bmltwf_bmlt_test_status" => get_option("bmltwf_bmlt_test_status", "failure"));

        return $this->handlerCore->bmltwf_rest_success($response);
    }

    // This is for testing username/password/server combination
    public function post_bmltserver_handler($request)
    {

        $username = $request['bmltwf_bmlt_username'];
        $password = $request['bmltwf_bmlt_password'];
        $server = $request['bmltwf_bmlt_server_address'];

        $data = array();

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        if ($result !== true) {

            $r = update_option("bmltwf_bmlt_test_status", "failure");

            // $result is a WP_Error
            $data["bmltwf_bmlt_test_status"] = "failure";
            $result->add_data($data);

            return $result;
        }

        $ret = $this->bmlt_integration->testServerAndAuth($username, $password, $server);

        if (is_wp_error($ret)) {

            $r = update_option("bmltwf_bmlt_test_status", "failure");
            $data["bmltwf_bmlt_test_status"] = "failure";
            return $this->handlerCore->bmltwf_rest_error_with_data('Server and Authentication test failed - ' . $ret->get_error_message(), 500, $data);
 
        } else {

            $r = update_option("bmltwf_bmlt_test_status", "success");
            $data["bmltwf_bmlt_test_status"] = "success";
            $data["message"] = "BMLT Server and Authentication test succeeded.";

            return $this->handlerCore->bmltwf_rest_success($data);
        }
    }

    public function patch_bmltserver_handler($request)
    {

        $username = $request['bmltwf_bmlt_username'];
        $password = $request['bmltwf_bmlt_password'];
        $server = $request['bmltwf_bmlt_server_address'];

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        $this->debug_log("check_bmltserver returned " . $result);

        if ($result !== true) {

            return $result;
        }

        update_option('bmltwf_bmlt_username', $username);

        $this->debug_log("encrypting BMLT password");

        $encrypted = $this->BMLTWF_WP_Options->secrets_encrypt(NONCE_SALT, $password);

        if (!is_array($encrypted)) {
            return $this->handlerCore->bmltwf_rest_failure('Error encrypting password.');
        }

        update_option('bmltwf_bmlt_password', $encrypted);
        update_option('bmltwf_bmlt_server_address', $server);

        return $this->handlerCore->bmltwf_rest_success('BMLT Server and Authentication details updated.');
    }

    public function get_bmltserver_geolocate_handler($request)
    {

        $address = $request->get_param('address');

        if (empty($address)) {
            return $this->handlerCore->bmltwf_rest_error('Empty address parameter', 422);
        }

        $location = $this->bmlt_integration->geolocateAddress($address);
        if (is_wp_error($location)) {
            return $location;
        }

        $this->debug_log("GMAPS location lookup returns = " . $location['latitude'] . " " . $location['longitude']);

        $change['latitude'] = $location['latitude'];
        $change['longitude'] = $location['longitude'];
        $change['message'] = 'Geolocation successful';
        return $change;
    }
}

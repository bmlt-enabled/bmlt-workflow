<?php 
namespace wbw\REST\Handlers;

use wbw\BMLT\Integration;
use wbw\REST\HandlerCore;
use wbw\WBW_WP_Options;

class BMLTServerHandler
{

    use \wbw\WBW_Debug;

    public function __construct($intstub = null, $wpoptionssstub = null)
    {
        if (empty($intstub))
        {
            $this->bmlt_integration = new Integration();
        }
        else
        {
            $this->bmlt_integration = $intstub;
        }

        if (empty($wpoptionssstub))
        {
            $this->WBW_WP_Options = new WBW_WP_Options();
        }
        else
        {
            $this->WBW_WP_Options = $wpoptionssstub;
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
            return $this->handlerCore->wbw_rest_error('Empty BMLT username parameter', 422);
        }
        if (empty($password)) {
            return $this->handlerCore->wbw_rest_error('Empty BMLT password parameter', 422);
        }
        if (empty($server)) {
            return $this->handlerCore->wbw_rest_error('Empty BMLT server parameter', 422);
        }
        if (substr($server, -1) !== '/') {
            return $this->handlerCore->wbw_rest_error('BMLT Server address missing trailiing /', 422);
        }

        return true;
    }

    public function get_bmltserver_handler($request)
    {
        

        $this->debug_log('get test results returning');
        $this->debug_log($this->WBW_WP_Options->wbw_get_option("wbw_bmlt_test_status", "failure"));

        $response = array("wbw_bmlt_test_status" => $this->WBW_WP_Options->wbw_get_option("wbw_bmlt_test_status", "failure"));

        return $this->handlerCore->wbw_rest_success($response);
    }

    // This is for testing username/password/server combination
    public function post_bmltserver_handler($request)
    {
        

        $username = $request['wbw_bmlt_username'];
        $password = $request['wbw_bmlt_password'];
        $server = $request['wbw_bmlt_server_address'];

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        if ($result !== true) {

            $r = update_option("wbw_bmlt_test_status", "failure");

            // $result is a WP_Error
            $data = array(
                "wbw_bmlt_test_status" => "failure"
            );
            $result->add_data($data);
    
            return $result;
        }

        $ret = $this->bmlt_integration->testServerAndAuth($username, $password, $server);

        if (is_wp_error($ret)) {

            $r = update_option("wbw_bmlt_test_status", "failure");

            $response = array(
                "wbw_bmlt_test_status" => "failure"
            );

            return $this->handlerCore->wbw_rest_error_with_data('Server and Authentication test failed - ' . $ret->get_error_message(), 500, $response);
        } else {

            $r = update_option("wbw_bmlt_test_status", "success");

            $response = array(
                "message" => "BMLT Server and Authentication test succeeded.",
                "wbw_bmlt_test_status" => "success"
            );
            return $this->handlerCore->wbw_rest_success($response);
        }
    }

    public function patch_bmltserver_handler($request)
    {
        

        $username = $request['wbw_bmlt_username'];
        $password = $request['wbw_bmlt_password'];
        $server = $request['wbw_bmlt_server_address'];

        $result = $this->check_bmltserver_parameters($username, $password, $server);
        $this->debug_log("check_bmltserver returned " .$result);

        if ($result !== true) {
            
            return $result;
        }

        update_option('wbw_bmlt_username', $username);

        $this->debug_log("encrypting BMLT password");

        $encrypted = $this->WBW_WP_Options->secrets_encrypt(NONCE_SALT, $password);

        if(!is_array($encrypted))
        {
            return $this->handlerCore->wbw_rest_failure('Error encrypting password.');

        }

        update_option('wbw_bmlt_password', $encrypted);
        update_option('wbw_bmlt_server_address', $server);

        return $this->handlerCore->wbw_rest_success('BMLT Server and Authentication details updated.');
    }

    public function get_bmltserver_geolocate_handler($request)
    {

        

        $address = $request->get_param('address');

        if (empty($address)) {
            return $this->handlerCore->wbw_rest_error('Empty address parameter', 422);
        }

        $location = $this->bmlt_integration->geolocateAddress($address);
        if (is_wp_error($location)) {
            return $location;
        }

        $this->debug_log("GMAPS location lookup returns = " . $location['latitude'] . " " . $location['longitude']);

        $change['latitude']= $location['latitude'];
        $change['longitude']= $location['longitude'];
        $change['message']='Geolocation successful';
        return $change;

    }

}
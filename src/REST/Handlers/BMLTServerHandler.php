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
use WP_Error;

class BMLTServerHandler
{

    use \bmltwf\BMLTWF_Debug;

    protected $bmlt_integration;
    protected $handlerCore;

    public function __construct($intstub = null)
    {
        if (empty($intstub)) {
            $this->bmlt_integration = new Integration();
        } else {
            $this->bmlt_integration = $intstub;
        }
        
        $this->handlerCore = new HandlerCore();

    }

    public function get_bmltserver_handler($request)
    {

        $this->debug_log('get test results returning');
        $this->debug_log(get_option("bmltwf_bmlt_test_status", "failure"));

        $response = array("bmltwf_bmlt_test_status" => get_option("bmltwf_bmlt_test_status", "failure"),
    "bmltwf_bmlt_server_version" => $this->bmlt_integration->bmlt_root_server_version);

        return $this->handlerCore->bmltwf_rest_success($response);
    }

    // This is for testing username/password/server combination
    public function post_bmltserver_handler($request)
    {

        $username = $request['bmltwf_bmlt_username'] ?? false;
        $password = $request['bmltwf_bmlt_password'] ?? false;
        $server = $request['bmltwf_bmlt_server_address'] ?? false;

        $data = array();

        $code = 422;

        $data["bmltwf_bmlt_server_status"] = "false";
        $data["bmltwf_bmlt_login_status"] = "unknown";

        if (empty($server)) {
            $message='Empty BMLT Root Server parameter';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        }

        if (substr($server, -1) !== '/') {
            $message='BMLT Root Server address missing trailing /';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        }

        if (!$this->bmlt_integration->is_valid_bmlt_server($server))
        {
            $message='Provided server does not appear to be a BMLT Root Server';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;

        }

        if (!$this->bmlt_integration->is_supported_server($server))
        {
            $message='Provided BMLT Root Server is not supported';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        }

        $data["bmltwf_bmlt_server_status"] = "true";
        $data["bmltwf_bmlt_login_status"] = "false";

        if (empty($username)) {
            $message='Empty BMLT username parameter';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        }

        if (empty($password)) {
            $message='Empty BMLT password parameter';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        }
        

        $ret = $this->bmlt_integration->testServerAndAuth($username, $password, $server);
        if (is_wp_error($ret)) {
            $message='BMLT Root Server Authentication test failed';
            $result = $this->handlerCore->bmltwf_rest_error($message, $code);
            $result->add_data($data);
            return $result;
        } 
        
        
        $data["bmltwf_bmlt_server_status"] = "true";
        $data["bmltwf_bmlt_login_status"] = "true";
        $data["bmltwf_bmlt_server_version"] = $this->bmlt_integration->bmlt_root_server_version;
        $data["message"] = "BMLT Root Server and Authentication test succeeded.";
        return $this->handlerCore->bmltwf_rest_success($data);
        
    }

    public function patch_bmltserver_handler($request)
    {

        $username = $request['bmltwf_bmlt_username'];
        $password = $request['bmltwf_bmlt_password'];
        $server = $request['bmltwf_bmlt_server_address'];

        update_option('bmltwf_bmlt_username', $username);

        $this->debug_log("encrypting BMLT password");

        if(defined('BMLTWF_RUNNING_UNDER_PHPUNIT'))
        {
            $nonce_salt = BMLTWF_PHPUNIT_NONCE_SALT;
        }
        else
        {
            $nonce_salt = NONCE_SALT;
        }

        $encrypted = $this->secrets_encrypt($nonce_salt, $password);

        if (!is_array($encrypted)) {
            return $this->handlerCore->bmltwf_rest_failure('Error encrypting password.');
        }

        update_option('bmltwf_bmlt_password', $encrypted);
        update_option('bmltwf_bmlt_server_address', $server);
        update_option("bmltwf_bmlt_test_status", "success");

        // store the most current configured server version
        $this->bmlt_integration->update_root_server_version();

        $data = array();

        $data["bmltwf_bmlt_server_version"] = $this->bmlt_integration->bmlt_root_server_version;
        $data["bmltwf_bmlt_test_status"] = "success";
        $data["message"]='BMLT Root Server and Authentication details updated.';

        return $this->handlerCore->bmltwf_rest_success($data);
    }

    private function secrets_encrypt($password, $secret)
    {

        $config = [
            'size'      => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES,
            'salt'      => random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES),
            'limit_ops' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
            'limit_mem' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE,
            'alg'       => SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
            'nonce'     => random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
        ];

        $key = hash_hkdf('sha256', $password, $config['size'], 'context');

        $encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $secret,
            $config['nonce'], // Associated Data
            $config['nonce'],
            $key
        );

        return [
            'config' => array_map('base64_encode', $config),
            'encrypted' => base64_encode($encrypted),
        ];
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

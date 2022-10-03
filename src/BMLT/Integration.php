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



namespace bmltwf\BMLT;

//  use bmltwf\BMLTWF_Debug;
use bmltwf\BMLTWF_WP_Options;

if ((!defined('ABSPATH') && (!defined('BMLTWF_RUNNING_UNDER_PHPUNIT')))) exit; // die if being called directly

class Integration
{

    use \bmltwf\BMLTWF_Debug;
    protected $cookies = null; // our authentication cookies
    protected $bmlt_root_server_version = null; // the version of bmlt root server we're authing against
    protected $v3_token = null; // v3 auth token

    public function __construct($cookies = null, $wpoptionssstub = null)
    {
        if (!empty($cookies)) {
            $this->cookies = $cookies;
        }

        if (empty($wpoptionssstub)) {
            $this->BMLTWF_WP_Options = new BMLTWF_WP_Options();
        } else {
            $this->BMLTWF_WP_Options = $wpoptionssstub;
        }

        $this->bmltwf_set_server_version($this->bmltwf_get_remote_server_version(get_option('bmltwf_bmlt_server_address')));
    }

    private function bmltwf_rest_error($message, $code)
    {
        return new \WP_Error('bmltwf_error', $message, array('status' => $code));
    }

    public function bmltwf_set_server_version($version)
    {
        $this->bmlt_root_server_version = $version;
    }

    // accepts raw string or array
    private function bmltwf_rest_success($message)
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

    private function bmltwf_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('bmltwf_error', $message, $data);
    }

    private function bmltwf_use_v3_auth()
    {
        if (version_compare($this->bmlt_root_server_version, "3.0.0", "lt")) {
            return false;
        } else {
            $this->debug_log("using v3 auth");
            return true;
        }
    }

    public function bmltwf_get_remote_server_version($server)
    {

        $url = $server . "client_interface/serverInfo.xml";
        $this->debug_log("url = " . $url);
        $headers = array(
            "Accept: */*",
        );

        $resp = wp_remote_get($url, array('headers' => $headers));
        $this->debug_log("WP_REMOTE_GET RETURNS");
        $this->debug_log(($resp));

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(wp_remote_retrieve_body($resp));
        if ($xml === false) {
            return false;
        } else {
            if (!($xml->serverVersion->readableString instanceof \SimpleXMLElement)) {
                return false;
            }

            return ($xml->serverVersion->readableString->__toString());
        }
    }
    /**
     * retrieve_single_meeting
     *
     * @param  int $meeting_id
     * @return void
     */
    public function retrieve_single_meeting($meeting_id)
    {

        $bmltwf_bmlt_server_address = get_option('bmltwf_bmlt_server_address');
        $url = $bmltwf_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&lang_enum=en&meeting_key_value=" . $meeting_id;
        $headers = array(
            "Accept: */*",
        );

        $resp = wp_remote_get($url, array('headers' => $headers));
        $this->debug_log("WP_REMOTE_GET RETURNS");
        $this->debug_log(($resp));

        if ((!is_array($resp)) ||  is_wp_error($resp)) {
            return $this->bmltwf_rest_error('Server error retrieving meeting', 500);
        }

        $body = wp_remote_retrieve_body($resp);

        $meetingarr = json_decode($body, true);
        if (empty($meetingarr[0])) {
            return $this->bmltwf_rest_error('Server error retrieving meeting', 500);
        }
        $meeting = $meetingarr[0];
        $this->debug_log("SINGLE MEETING");
        $this->debug_log(($meeting));
        // how possibly can we get a meeting that is not the same as we asked for
        if ($meeting['id_bigint'] != $meeting_id) {
            return $this->bmltwf_rest_error('Server error retrieving meeting', 500);
        }
        return $meeting;
    }

    public function testServerAndAuth2x($username, $password, $server)
    {
        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => $username,
            'c_comdef_admin_password' => $password
        );

        $url = $server . "index.php";
        $this->debug_log($url);
        $ret = \wp_safe_remote_post($url, array('body' => http_build_query($postargs)));
        $this->debug_log(($ret));

        $response_code = \wp_remote_retrieve_response_code($ret);

        if ($response_code != 200) {
            return new \WP_Error('bmltwf', 'check BMLT server address');
        }
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            return new \WP_Error('bmltwf', 'check username and password details');
        }
        return true;
    }

    public function testServerAndAuth3x($username, $password, $server)
    {
        $postargs = array(
            'username' => $username,
            'password' => $password
        );

        $url = $server . "api/v1/auth/token";
        $this->debug_log($url);
        $response = \wp_safe_remote_post($url, array('body' => http_build_query($postargs)));
        $this->debug_log(($response));

        $response_code = \wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            return new \WP_Error('bmltwf', 'check BMLT server address');
        }

        $auth_details = json_decode(wp_remote_retrieve_body($response), true);
        $this->debug_log($auth_details['token']);

        return true;
    }

    public function getMeetingFormats()
    {

        $req = array();
        $req['admin_action'] = 'get_format_info';

        // get an xml for a workaround
        $response = $this->postAuthenticatedRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
        if (is_wp_error($response)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }


        // $this->debug_log(wp_remote_retrieve_body($response));
        // $formatarr = json_decode(wp_remote_retrieve_body($response), true);
        $xml = simplexml_load_string(wp_remote_retrieve_body($response));
        // $this->debug_log("XML RESPONSE");
        // $this->debug_log(wp_remote_retrieve_body($response));
        $formatarr = json_decode(json_encode($xml), 1);

        // $this->debug_log(($formatarr));

        $newformat = array();
        foreach ($formatarr['row'] as $key => $value) {
            $formatid = $value['id'];
            unset($value['id']);
            $newformat[$formatid] = $value;
        }
        // $this->debug_log("NEWFORMAT");
        // $this->debug_log(($newformat));

        return $newformat;
    }

    /**
     * getMeetingStates
     *
     * @return \WP_Error|bool|array
     */
    public function getMeetingStates()
    {
        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        if (is_wp_error($response)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // $this->debug_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        if (!empty($arr['meeting_states_and_provinces'])) {
            $states = explode(',', $arr['meeting_states_and_provinces']);
            return $states;
        }
        return false;
    }

    /**
     * getMeetingCounties
     *
     * @return \WP_Error|bool|array
     */
    public function getMeetingCounties()
    {
        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        if (is_wp_error($response)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve server info');
        }
        // $this->debug_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        // 
        // $this->debug_log("***");
        // $this->debug_log(($arr));
        if (!empty($arr['meeting_counties_and_sub_provinces'])) {
            $counties = explode(',', $arr['meeting_counties_and_sub_provinces']);
            return $counties;
        }
        return false;
    }

    /**
     * getGmapsKey
     *
     * workaround for client/server side maps key issues
     * 
     * @return \WP_Error|string
     */
    public function getGmapsKey()
    {

        $ret = $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            // $this->debug_log("*** AUTH ERROR");
            // $this->debug_log(($ret));
            return $ret;
        }

        $url = get_option('bmltwf_bmlt_server_address') . "index.php";
        // $this->debug_log("*** ADMIN URL ".$url);

        $resp = $this->get($url, $this->cookies);
        // $this->debug_log("*** ADMIN PAGE");
        // $this->debug_log(wp_remote_retrieve_body($resp));

        preg_match('/"google_api_key":"(.*?)",/', wp_remote_retrieve_body($resp), $matches);
        return $matches[1];
    }

    public function geolocateAddress($address)
    {
        $key = $this->getGmapsKey();

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $key;

        $this->debug_log("*** GMAPS URL");
        $this->debug_log($url);

        $headers = array(
            "Accept: */*",
        );

        $resp = wp_remote_get($url, array('headers' => $headers));

        if ((!is_array($resp)) ||  is_wp_error($resp)) {
            return $this->bmltwf_rest_error('Server error geolocating address', 500);
        }

        $body = wp_remote_retrieve_body($resp);

        if (!$body) {
            return new \WP_Error('bmltwf', 'Server error geolocating address');
        }

        $this->debug_log("*** GMAPS RESPONSE");
        $this->debug_log($body);

        $geo = json_decode($body, true);
        if ((empty($geo)) || (empty($geo['status']))) {
            return new \WP_Error('bmltwf', 'Server error geolocating address');
        }
        if (($geo['status'] === "ZERO_RESULTS") || empty($geo['results'][0]['geometry']['location']['lat']) || empty($geo['results'][0]['geometry']['location']['lng'])) {
            return new \WP_Error('bmltwf', 'Could not geolocate meeting address. Please try amending the address with additional/correct details.');
        } else {
            $location = array();
            $location['latitude'] = $geo['results'][0]['geometry']['location']['lat'];
            $location['longitude'] = $geo['results'][0]['geometry']['location']['lng'];
            return $location;
        }
    }

    private function authenticateRootServer()
    {

        if ($this->cookies == null) {
            $encrypted = get_option('bmltwf_bmlt_password');
            $this->debug_log("retrieved encrypted bmlt password");
            // $this->debug_log(($encrypted));

            if ($encrypted === false) {
                return new \WP_Error('bmltwf', 'Error unpacking password.');
            }

            if (defined('BMLTWF_RUNNING_UNDER_PHPUNIT')) {
                $nonce_salt = BMLTWF_PHPUNIT_NONCE_SALT;
            } else {
                $nonce_salt = NONCE_SALT;
            }

            $decrypted = $this->BMLTWF_WP_Options->secrets_decrypt($nonce_salt, $encrypted);
            if ($decrypted === false) {
                return new \WP_Error('bmltwf', 'Error decrypting password.');
            }

            if ($this->bmltwf_use_v3_auth()) {
                $postargs = array(
                    'username' => get_option('bmltwf_bmlt_username'),
                    'password' => $decrypted
                );
                $this->debug_log("inside authenticateRootServer v3 auth");
                $url = get_option('bmltwf_bmlt_server_address') . "api/v1/auth/token";
                $this->debug_log($url);
                $response = \wp_safe_remote_post($url, array('body' => http_build_query($postargs)));
                $this->debug_log(($response));

                $response_code = \wp_remote_retrieve_response_code($response);

                if ($response_code != 200) {
                    return new \WP_Error('bmltwf', 'authenticateRootServer: Authentication Failure');
                }

                $auth_details = json_decode(wp_remote_retrieve_body($response), true);
                $this->v3_token = $auth_details['token'];
            } else {
                // legacy auth
                $postargs = array(
                    'admin_action' => 'login',
                    'c_comdef_admin_login' => get_option('bmltwf_bmlt_username'),
                    'c_comdef_admin_password' => $decrypted
                );
                $url = get_option('bmltwf_bmlt_server_address') . "index.php";

                // $this->debug_log("AUTH URL = " . $url);
                $ret = $this->post($url, null, $postargs);

                if (is_wp_error($ret)) {
                    return new \WP_Error('bmltwf', 'authenticateRootServer: Server Failure');
                }

                if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
                {
                    $this->cookies = null;
                    return new \WP_Error('bmltwf', 'authenticateRootServer: Authentication Failure');
                }

                $this->cookies = \wp_remote_retrieve_cookies($ret);
            }
        }
        return true;
    }

    private function set_args($cookies, $body = null)
    {
        $args = array(
            'timeout' => '120',
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36'
            ),
            'cookies' => isset($cookies) ? $cookies : null,
            'body' => isset($body) ? $body : null

        );
        return $args;
    }

    private function get($url, $cookies = null)
    {
        if ($this->bmltwf_use_v3_auth()) {
            $this->debug_log("inside get v3 auth");

            if (!$this->v3_token) {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
            }
            $ret = \wp_safe_remote_get($url, $this->set_args($this->v3_token));
            return $ret;
        } else {
            $ret = \wp_safe_remote_get($url, $this->set_args($cookies));
            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', \wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
                // try once more in case it was a session timeout
                $ret = wp_safe_remote_get($url, $this->set_args($cookies));
            }
            return $ret;
        }
    }

    private function post($url, $cookies = null, $postargs)
    {

        if ($this->bmltwf_use_v3_auth()) {
            $this->debug_log("inside post v3 auth");

            if (!$this->v3_token) {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
            }
            $ret = \wp_safe_remote_post($url, $this->set_args($this->v3_token, http_build_query($postargs)));
            return $ret;
        } else {
            $this->debug_log("POSTING URL = " . $url);
            // $this->debug_log(($this->set_args($cookies, http_build_query($postargs))));
            // $this->debug_log("*********");
            $ret = \wp_safe_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));

            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', \wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }

                // try once more in case it was a session timeout
                $ret = \wp_safe_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
            }
            return $ret;
        }
    }

    private function postsemantic($url, $cookies = null, $postargs)
    {


        $this->debug_log("POSTING SEMANTIC URL = " . $url);
        // $this->debug_log(($this->set_args($cookies, http_build_query($postargs))));
        // $this->debug_log("*********");
        $newargs = '';
        foreach ($postargs as $key => $value) {
            switch ($key) {
                case ('admin_action'):
                case ('meeting_id'):
                case ('flat');
                    $newargs .= $key . '=' . $value;
                    break;
                default:
                    $newargs .= "meeting_field[]=" . $key . ',' . $value;
            }
            $newargs .= '&';
        }
        if ($newargs != '') {
            // chop trailing &
            $newargs = substr($newargs, 0, -1);
            // $this->debug_log("our post body is " . $newargs);
            $ret = \wp_safe_remote_post($url, $this->set_args($cookies, $newargs));
            // $this->debug_log(($ret));
            return $ret;
        }
    }

    /**
     * postAuthenticatedRootServerRequest
     *
     * @param  string $url
     * @param  array $postargs
     * @return array|\WP_Error
     */
    public function postAuthenticatedRootServerRequest($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            return $ret;
        }
        if (!(is_array($postargs))) {
            return $this->bmltwf_rest_error("Missing post parameters", "bmltwf_bmlt_integration");
        }
        return $this->post(get_option('bmltwf_bmlt_server_address') . $url, $this->cookies, $postargs);
    }

    /**
     * postUnauthenticatedRootServerRequest
     *
     * @param  string $url
     * @param  array $postargs
     * @return array|\WP_Error
     */
    public function postUnauthenticatedRootServerRequest($url, $postargs)
    {
        if (!(is_array($postargs))) {
            return $this->bmltwf_rest_error("Missing post parameters", "bmltwf_bmlt_integration");
        }
        $val = $this->post(get_option('bmltwf_bmlt_server_address') . $url, null, $postargs);
        // $this->debug_log(($val));
        return $val;
    }

    /**
     * postAuthenticatedRootServerRequestSemantic
     *
     * @param  string $url
     * @param  array $postargs
     * @return array|\WP_Error
     */
    public function postAuthenticatedRootServerRequestSemantic($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();

        if (is_wp_error($ret)) {
            return $ret;
        }
        if (!(is_array($postargs))) {
            return $this->bmltwf_rest_error("Missing post parameters", "bmltwf_bmlt_integration");
        }

        return $this->postsemantic(get_option('bmltwf_bmlt_server_address') . $url, $this->cookies, $postargs);
    }
}

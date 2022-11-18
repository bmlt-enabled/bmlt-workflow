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
use bmltwf\REST\HandlerCore;


class Integration
{

    use \bmltwf\BMLTWF_Debug;
    protected $cookies = null; // our authentication cookies
    protected $bmlt_root_server_version = null; // the version of bmlt root server we're authing against
    protected $v3_access_token = null; // v3 auth token
    protected $v3_access_token_expires_at = null; // v3 auth token expiration
    protected $bmltwf_bmlt_user_id; // user id of the workflow bot

    public function __construct($cookies = null, $root_server_version = null, $access_token = null, $token_expiry = null )
    {
        if (!empty($cookies)) {
            $this->cookies = $cookies;
        }
        if (!empty($access_token))
        {
            $this->v3_access_token = $access_token;
        }
        if (!empty($token_expiry))
        {
            $this->v3_access_token_expires_at = $token_expiry;
        }

        if (empty($root_server_version)) {
            $version = \get_option('bmltwf_bmlt_server_version');
            if ($version) {
                $this->bmlt_root_server_version = $version;
            }
            else
            {
                $this->bmlt_root_server_version = $this->bmltwf_get_remote_server_version(\get_option('bmltwf_bmlt_server_address'), false);
            }
        } else {
            $this->bmlt_root_server_version = $root_server_version;
        }
    }

    private function bmltwf_integration_error($message, $code)
    {
        return new \WP_Error('bmltwf_error', $message, array('status' => $code));
    }

    // accepts raw string or array
    private function bmltwf_integration_success($message)
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

    private function bmltwf_integration_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('bmltwf_error', $message, $data);
    }

    private function convertv2meetingtov3($meeting)
    {
        $fromto = array();
        $fromto['service_body_bigint'] = 'serviceBodyId';
        $fromto['venue_type'] = 'venueType';
        $fromto['weekday_tinyint'] = 'day';
        $fromto['start_time'] = 'startTime';

        // dont need this any more
        unset($meeting['id_bigint']);

        // change all our fields over
        foreach ($fromto as $from => $to) {
            $here = $meeting[$from] ?? false;
            if ($here) {
                $meeting[$to] = $meeting[$from];
                unset($meeting[$from]);
            }
        }

        // special cases
        $here = $meeting['duration_time'] ?? false;
        if ($here) {
            $time = explode(':', $meeting['duration_time']);
            $meeting['duration'] = $time[0] . ":" . $time[1];
            unset($meeting['duration_time']);
        }

        $here = $meeting['format_shared_id_list'] ?? false;
        if ($here) {
            $meeting['formatIds'] = explode(',', $meeting['format_shared_id_list']);
            unset($meeting['format_shared_id_list']);
        }
        return $meeting;
    }

    public function is_v3_server()
    {
        if (version_compare($this->bmlt_root_server_version, "3.0.0", "lt")) {
            return false;
        } else {
            $this->debug_log("using v3 auth");
            return true;
        }
    }

    public function bmltwf_get_remote_server_version($server, $cache = true)
    {
        if ($cache) {
            $version = get_option('bmltwf_bmlt_server_version');
            if ($version) {
                return $version;
            }
        }
        $url = $server . "client_interface/serverInfo.xml";
        $this->debug_log("url = " . $url);
        $headers = array(
            "Accept: */*",
        );

        $resp = wp_remote_get($url, array('headers' => $headers));
        $this->debug_log("wp_remote_get RETURNS");
        $this->debug_log(($resp));

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(\wp_remote_retrieve_body($resp));
        if ($xml === false) {
            return false;
        } else {
            if (!($xml->serverVersion->readableString instanceof \SimpleXMLElement)) {
                return false;
            }
            $version = $xml->serverVersion->readableString->__toString();
            update_option('bmltwf_bmlt_server_version', $version);
            $this->bmlt_root_server_version = $version;
            return $version;
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
        $this->debug_log("wp_remote_get RETURNS");
        $this->debug_log(($resp));

        if ((!is_array($resp)) ||  is_wp_error($resp)) {
            return $this->bmltwf_integration_error('Server error retrieving meeting', 500);
        }

        $body = wp_remote_retrieve_body($resp);

        $meetingarr = json_decode($body, true);
        if (empty($meetingarr[0])) {
            return $this->bmltwf_integration_error('Server error retrieving meeting', 500);
        }
        $meeting = $meetingarr[0];
        $this->debug_log("SINGLE MEETING");
        $this->debug_log(($meeting));
        // how possibly can we get a meeting that is not the same as we asked for
        if ($meeting['id_bigint'] != $meeting_id) {
            return $this->bmltwf_integration_error('Server error retrieving meeting', 500);
        }
        return $meeting;
    }

    public function testServerAndAuth($username, $password, $server)
    {
        $rsv = $this->bmltwf_get_remote_server_version($server, false);
        if (version_compare($rsv, "3.0.0", "lt")) {
            $ret = $this->testServerAndAuthv2($username, $password, $server);
        } else {
            $ret = $this->testServerAndAuthv3($username, $password, $server);
        }

        return $ret;
    }

    public function testServerAndAuthv2($username, $password, $server)
    {

        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => $username,
            'c_comdef_admin_password' => $password
        );
        $url = $server . "index.php";
        $this->debug_log($url);
        $ret = \wp_remote_post($url, array('body' => http_build_query($postargs)));
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

    public function testServerAndAuthv3($username, $password, $server)
    {


        $postargs = array(
            'username' => $username,
            'password' => $password
        );

        $url = $server . "api/v1/auth/token";
        $this->debug_log($url);
        $response = \wp_remote_post($url, array('body' => http_build_query($postargs)));
        $this->debug_log(($response));

        $response_code = \wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            return new \WP_Error('bmltwf', 'check BMLT server address');
        }

        $auth_details = json_decode(\wp_remote_retrieve_body($response), true);
        $this->debug_log($auth_details['access_token']);

        return true;
    }

    public function updateMeeting($change)
    {
        if ($this->is_v3_server()) {
            return $this->updateMeetingv3($change);
        } else {
            return $this->updateMeetingv2($change);
        }
    }

    private function updateMeetingv2($change)
    {
        $changearr = array();
        $changearr['bmlt_ajax_callback'] = 1;
        $changearr['set_meeting_change'] = json_encode($change);
        $this->debug_log("CHANGE");
        $this->debug_log(($changearr));

        $response = $this->postAuthenticatedRootServerRequest('', $changearr);

        if (is_wp_error($response)) {
            return $this->bmltwf_integration_error('BMLT Communication Error - Check the BMLT configuration settings', 500);
        }

        $this->debug_log("CHANGE RESPONSE");
        $this->debug_log(($response));

        $json = wp_remote_retrieve_body($response);
        $rep = str_replace("'", '"', $json);

        $dec = json_decode($rep, true);
        if (((isset($dec['error'])) && ($dec['error'] === true)) || (empty($dec[0]))) {
            return $this->bmltwf_integration_error('BMLT Communication Error - Meeting change failed', 500);
        }

        return true;
    }

    private function updateMeetingv3($change)
    {
        $this->debug_log("CHANGE before");
        $this->debug_log($change);

        $meeting_id = $change['id_bigint'] ?? false;

        $change = $this->convertv2meetingtov3($change);

        $this->debug_log("CHANGE after");
        $this->debug_log($change);

        $this->debug_log("inside updateMeetingv3 auth");

        if (!$meeting_id) {
            return new \WP_Error('bmltwf', 'updateMeetingv3: No meeting ID present');
        }

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting updateMeetingv3 authenticateRootServer failed");
                return $ret;
            }
        }

        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings/' . $meeting_id;

        $response = \wp_remote_request($url, $this->set_args(null, $change, array("Authorization" => "Bearer " . $this->v3_access_token), 'PATCH'));
        $this->debug_log("v3 API RESPONSE");
        $this->debug_log($response);

        if (\wp_remote_retrieve_response_code($response) != 204) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        return true;
    }

    public function getServiceBodies()
    {
        if ($this->is_v3_server()) {
            return $this->getServiceBodiesv3();
        } else {
            return $this->getServiceBodiesv2();
        }
    }

    private function getServiceBodiesv2()
    {
        $arr = $this->getServiceBodiesPermissionv2();

        if (is_wp_error($arr)) {
            return $this->bmltwf_integration_error('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 500);
        }

        if (empty($arr['service_body'])) {
            return $this->bmltwf_integration_error('No service bodies visible - Check the BMLT Root Server configuration settings', 500);
        }

        // create an array of the service bodies that we are able to see
        $editable = array();
        foreach ($arr['service_body'] as $key => $sb) {

            $permissions = $sb['permissions'] ?? 0;
            $id = $sb['id'] ?? 0;

            if ($id) {
                if (($permissions === 2) || ($permissions === 3)) {
                    $editable[$id] = true;
                }
            }
        }

        $req = array();
        $req['admin_action'] = 'get_service_body_info';

        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServiceBodies', $req);
        if (is_wp_error($response)) {
            return $this->bmltwf_integration_error('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 500);
        }

        $arr = json_decode(\wp_remote_retrieve_body($response), 1);

        // $this->debug_log("SERVICE BODY JSON");
        // $this->debug_log(($arr));

        // make our list of editable service bodies
        foreach ($arr as $key => $value) {

            $id = $value['id'] ?? 0;
            $name = $value['name'] ?? 0;
            $description = $value['description'] ?? '';

            // must have an id and name
            if ($id && $name) {
                // check we can see the service body from permissions above
                $is_editable = $editable[$id] ?? false;
                if ($is_editable) {
                    $sblist[$id] = array('name' => $name, 'description' => $description);
                }
            }
        }
        return $sblist;
    }

    private function getServiceBodiesv3()
    {
        $this->debug_log("inside getServiceBodies v3 auth");
        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting getServiceBodies authenticateRootServer failed");
                return $ret;
            }
        }
        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/servicebodies';
        $this->debug_log($url);
        $response = \wp_remote_get($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token)));
        $this->debug_log("v3 API RESPONSE");
        $this->debug_log(\wp_remote_retrieve_body($response));

        if (\wp_remote_retrieve_response_code($response) != 200) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        $response = json_decode(\wp_remote_retrieve_body($response), 1);
        foreach ($response as $key => $sb) {
            $sblist[$sb['id']] = array('name' => $sb['name'], 'description' => $sb['description']);
        }
        return $sblist;
    }

    public function deleteMeeting($meeting_id)
    {
        if ($this->is_v3_server()) {
            return $this->deleteMeetingv3($meeting_id);
        } else {
            return $this->deleteMeetingv2($meeting_id);
        }
    }

    private function deleteMeetingv2($meeting_id)
    {
        $changearr = array();
        $changearr['bmlt_ajax_callback'] = 1;
        $changearr['delete_meeting'] = $meeting_id;

        $this->debug_log("DELETE SEND");
        $this->debug_log(($changearr));

        $response = $this->postAuthenticatedRootServerRequest('', $changearr);

        if (is_wp_error($response)) {
            return $this->bmltwf_integration_error('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 500);
        }

        // $rep = str_replace("'", '"', $response);
        // $this->debug_log("JSON RESPONSE");
        // $this->debug_log(($rep));

        // $arr = json_decode($rep, true);
        $arr = json_decode(\wp_remote_retrieve_body($response), 1);

        $this->debug_log("DELETE RESPONSE");
        $this->debug_log(($arr));

        if ((isset($arr['success'])) && ($arr['success'] != 1)) {
            return $this->bmltwf_integration_error('BMLT Communication Error - Meeting deletion failed', 500);
        }
        if ((!empty($arr['report'])) && ($arr['report'] != $meeting_id)) {
            return $this->bmltwf_integration_error('BMLT Communication Error - Meeting deletion failed', 500);
        }
        return true;
    }

    private function deleteMeetingv3($meeting_id)
    {
        $this->debug_log("inside deleteMeeting v3 auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
        }

        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings/' . $meeting_id;
        $args = $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token), 'DELETE');
        $response = \wp_remote_request($url, $args);

        if (\wp_remote_retrieve_response_code($response) != 204) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        return true;
    }

    public function getServiceBodiesPermissionv2()
    {
        $req = array();
        $req['admin_action'] = 'get_permissions';

        $response = $this->postAuthenticatedRootServerRequest('local_server/server_admin/json.php', $req);
        if (is_wp_error($response)) {
            return $this->bmltwf_integration_error('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 500);
        }

        $arr = json_decode(\wp_remote_retrieve_body($response), 1);

        return $arr;
    }

    public function getMeetingFormats()
    {
        if ($this->is_v3_server()) {
            return $this->getMeetingFormatsv3();
        } else {
            return $this->getMeetingFormatsv2();
        }
    }

    public function getMeetingFormatsv3()
    {
        $this->debug_log("inside getMeetingFormats v3 auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
        }

        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/formats';
        $args = $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token));
        $ret = \wp_remote_get($url, $args);
        $formatarr = json_decode(\wp_remote_retrieve_body($ret), 1);

        $newformat = array();
        foreach ($formatarr as $key => $value) {
            $formatid = $value['id'];
            $newvalue = array();
            $newvalue['world_id'] = $value['worldId'];
            foreach ($value['translations'] as $key1 => $value1) {
                if ($value1['language'] === 'en') {
                    $newvalue['lang'] = 'en';
                    $newvalue['description_string'] = $value1['description'];
                    $newvalue['name_string'] = $value1['name'];
                    $newvalue['key_string'] = $value1['key'];
                    break;
                }
            }
            $newformat[$formatid] = $newvalue;
        }
        $this->debug_log("NEWFORMAT");
        $this->debug_log(($newformat));

        return $newformat;

    }

    public function getMeetingFormatsv2()
    {

        $req = array();
        $req['admin_action'] = 'get_format_info';

        // get an xml for a workaround
        $response = $this->postAuthenticatedRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }

        // $this->debug_log(\wp_remote_retrieve_body($response));
        // $formatarr = json_decode(\wp_remote_retrieve_body($response), true);
        $xml = simplexml_load_string(\wp_remote_retrieve_body($response));
        // $this->debug_log("XML RESPONSE");
        // $this->debug_log(\wp_remote_retrieve_body($response));
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
        // $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');

        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true)[0];
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
        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');
        // $formatarr = json_decode(\wp_remote_retrieve_body($ret), 1);

        // $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        $this->debug_log("getMeetingCounties response");
        $this->debug_log($response);
        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve server info');
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true)[0];
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
            $this->debug_log("*** AUTH ERROR");
            $this->debug_log(($ret));
            return $ret;
        }

        $url = get_option('bmltwf_bmlt_server_address') . "index.php";
        $this->debug_log("*** ADMIN URL ".$url);

        $resp = $this->get($url, $this->cookies);
        $this->debug_log("*** ADMIN PAGE");
        $this->debug_log(\wp_remote_retrieve_body($resp));

        preg_match('/"google_api_key":"(.*?)",/', \wp_remote_retrieve_body($resp), $matches);
        return $matches[1];
    }

    public function createMeeting($meeting)
    {
        if ($this->is_v3_server()) {
            return $this->createMeetingv3($meeting);
        } else {
            return $this->createMeetingv2($meeting);
        }
    }

    private function createMeetingv3($meeting)
    {
        $this->debug_log("CHANGE before");
        $this->debug_log($meeting);

        $meeting = $this->convertv2meetingtov3($meeting);

        $this->debug_log("CHANGE after");
        $this->debug_log($meeting);

        $this->debug_log("inside createMeetingv3 auth");


        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting createMeetingv3 authenticateRootServer failed");
                return $ret;
            }
        }
        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings';

        $response = \wp_remote_post($url, $this->set_args(null, $meeting, array("Authorization" => "Bearer " . $this->v3_access_token)));
        $this->debug_log("v3 API RESPONSE");
        $this->debug_log($response);

        if (\wp_remote_retrieve_response_code($response) != 201) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        return true;
    }

    private function createMeetingv2($meeting)
    {
        // workaround for semantic new meeting bug
        $meeting['id_bigint'] = 0;

        // handle publish/unpublish here
        $meeting['published'] = 1;
        $changearr = array();
        $changearr['bmlt_ajax_callback'] = 1;
        $changearr['set_meeting_change'] = json_encode($meeting);
        $this->debug_log("posting change");
        $this->debug_log($changearr);

        $response = $this->postAuthenticatedRootServerRequest('', $changearr);
        $this->debug_log("posted change");

        if (is_wp_error($response)) {
            return $this->bmltwf_integration_error('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 500);
        }
        return true;
    }

    public function isAutoGeocodingEnabled()
    {
        if ($this->is_v3_server()) {
            return $this->isAutoGeocodingEnabledv3();
        } else {
            return $this->isAutoGeocodingEnabledv2();
        }
    }

    private function isAutoGeocodingEnabledv3()
    {
        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');
        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        $arr = json_decode(\wp_remote_retrieve_body($response), true)[0];
        if ((!empty($arr['auto_geocoding_enabled']))) {

            return $arr['auto_geocoding_enabled'] === "true" ? true : false;
        }
        return false;
    }

    public function isAutoGeocodingEnabledv2()
    {
        $ret = $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            return $ret;
        }

        $url = get_option('bmltwf_bmlt_server_address') . "index.php";

        $resp = $this->get($url, $this->cookies);

        preg_match('/"auto_geocoding_enabled":(?:(true)|(false)),/', wp_remote_retrieve_body($resp), $matches);
        $this->debug_log("matches: ");
        $this->debug_log($matches);
        $auto = $matches[1] === "true" ? true : false;
        $this->debug_log("auto geocoding check returns ");

        if ($auto) {
            $this->debug_log("true");
        } else {
            $this->debug_log("false");
        }

        return $auto;
    }


    public function getDefaultLatLong()
    {
        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');

        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', 'BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true)[0];
        if ((!empty($arr['centerLongitude'])) && (!empty($arr['centerLatitude']))) {

            return array('longitude' => $arr['centerLongitude'], 'latitude' => $arr['centerLatitude']);
        }
        return false;
    }

    public function geolocateAddress($address)
    {
        $key = $this->getGmapsKey();
        if (\is_wp_error($key))
        {
            return $this->bmltwf_integration_error('Server error geolocating address', 500);
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $key;

        $this->debug_log("*** GMAPS URL");
        $this->debug_log($url);

        $headers = array(
            "Accept: */*",
        );

        $resp = \wp_remote_get($url, array('headers' => $headers));

        if ((!is_array($resp)) ||  is_wp_error($resp)) {
            return $this->bmltwf_integration_error('Server error geolocating address', 500);
        }

        $body = \wp_remote_retrieve_body($resp);

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

    private function secrets_decrypt($password, $data)
    {
        $this->debug_log($data);
        $config = array_map('base64_decode', $data['config']);
        $encrypted = base64_decode($data['encrypted']);

        $key = hash_hkdf('sha256', $password, $config['size'], 'context');

        return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $encrypted,
            $config['nonce'], // Associated Data
            $config['nonce'],
            $key
        );
    }

    public function is_v3_token_valid()
    {
        if (!$this->v3_access_token || $this->v3_access_token_expires_at < time())
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    private function authenticateRootServer()
    {
        if ($this->is_v3_server()) {
            return $this->authenticateRootServerv3();
        } else {
            return $this->authenticateRootServerv2();
        }
    }

    private function decodeBMLTPassword()
    {
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

        $decrypted = $this->secrets_decrypt($nonce_salt, $encrypted);
        if ($decrypted === false) {
            return new \WP_Error('bmltwf', 'Error decrypting password.');
        }
        return $decrypted;
    }

    private function authenticateRootServerv3()
    {
        $decrypted = $this->decodeBMLTPassword();
        if(\is_wp_error($decrypted))
        {
            return $decrypted;
        }

        if (!$this->is_v3_token_valid()) {

            $postargs = array(
                'username' => get_option('bmltwf_bmlt_username'),
                'password' => $decrypted
            );
            $this->debug_log("inside authenticateRootServer v3 auth");
            $url = get_option('bmltwf_bmlt_server_address') . "api/v1/auth/token";
            $this->debug_log($url);
            $response = \wp_remote_post($url, array('body' => http_build_query($postargs)));
            $this->debug_log(($response));

            $response_code = \wp_remote_retrieve_response_code($response);

            if ($response_code != 200) {
                return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
            }

            $auth_details = json_decode(\wp_remote_retrieve_body($response), true);
            $this->v3_access_token = $auth_details['access_token'];
            $this->v3_expires_at = $auth_details['expires_at'];
        }
        return true;
    }

    private function authenticateRootServerv2()
    {

        $decrypted = $this->decodeBMLTPassword();
        if(\is_wp_error($decrypted))
        {
            return $decrypted;
        }

        // legacy auth
        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => get_option('bmltwf_bmlt_username'),
            'c_comdef_admin_password' => $decrypted
        );
        $url = get_option('bmltwf_bmlt_server_address') . "index.php";

        // $this->debug_log("AUTH URL = " . $url);
        // $ret = $this->post($url, null, $postargs);
        $ret = \wp_remote_post($url, $this->set_args(null, http_build_query($postargs)));

        if ((is_wp_error($ret)) || (\wp_remote_retrieve_response_code($ret) != 200)) {
            return new \WP_Error('bmltwf', 'authenticateRootServer: Server Failure');
        }

        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            $this->cookies = null;
            return new \WP_Error('bmltwf', 'authenticateRootServer: Authentication Failure');
        }

        $this->cookies = \wp_remote_retrieve_cookies($ret);
        return true;
    }

    private function set_args($cookies, $body = null, $headers = null, $method = null)
    {
        $newheaders =  array(
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36',
            'Accept' => 'application/json'
        );

        if ($headers) {
            $newheaders = array_merge($headers, $newheaders);
        }
        $this->debug_log('SET_ARGS headers');
        $this->debug_log($headers);
        $this->debug_log('SET_ARGS merged headers');
        $this->debug_log($newheaders);
        $args = array(
            'timeout' => '120',
            'headers' => $newheaders,
            'cookies' => isset($cookies) ? $cookies : null,
            'body' => isset($body) ? $body : null
        );
        if ($method) {
            $args['method'] = $method;
        }

        $this->debug_log("set_args:");
        $this->debug_log($args);

        return $args;
    }

    private function get($url, $cookies = null)
    {
        if ($this->is_v3_server()) {
            $this->debug_log("inside get v3 auth");

            if (!$this->is_v3_token_valid()) {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
            }
            $ret = \wp_remote_get($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token)));
            return $ret;
        } else {
            $ret = \wp_remote_get($url, $this->set_args($cookies));
            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', \wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
                // try once more in case it was a session timeout
                $ret = wp_remote_get($url, $this->set_args($cookies));
            }
            return $ret;
        }
    }

    private function post($url, $postargs, $cookies = null)
    {

        if ($this->is_v3_server()) {
            $this->debug_log("inside post v3 auth");

            if (!$this->is_v3_token_valid()) {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }
            }
            $ret = \wp_remote_post($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token), http_build_query($postargs)));
            return $ret;
        } else {
            $this->debug_log("POSTING URL = " . $url);
            // $this->debug_log(($this->set_args($cookies, http_build_query($postargs))));
            // $this->debug_log("*********");
            $ret = \wp_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));

            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', \wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $ret =  $this->authenticateRootServer();
                if (is_wp_error($ret)) {
                    return $ret;
                }

                // try once more in case it was a session timeout
                $ret = \wp_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
            }
            return $ret;
        }
    }

    private function postsemantic($url, $postargs, $cookies = null)
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
            $ret = \wp_remote_post($url, $this->set_args($cookies, $newargs));
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
            return $this->bmltwf_integration_error("Missing post parameters", "bmltwf_bmlt_integration");
        }
        return $this->post(get_option('bmltwf_bmlt_server_address') . $url, $postargs, $this->cookies);
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
            return $this->bmltwf_integration_error("Missing post parameters", "bmltwf_bmlt_integration");
        }
        $val = $this->post(get_option('bmltwf_bmlt_server_address') . $url, $postargs, null);
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
            return $this->bmltwf_integration_error("Missing post parameters", "bmltwf_bmlt_integration");
        }

        return $this->postsemantic(get_option('bmltwf_bmlt_server_address') . $url, $postargs, $this->cookies);
    }
}

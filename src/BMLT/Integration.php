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

class Integration
{

    use \bmltwf\BMLTWF_Debug;

    protected $cookies = null; // our authentication cookies
    public $bmlt_root_server_version = null; // the version of bmlt root server we're authing against
    protected $v3_access_token = null; // v3 auth token
    protected $v3_access_token_expires_at = null; // v3 auth token expiration
    protected $bmltwf_bmlt_user_id; // user id of the workflow bot

    public function __construct($cookies = null, $root_server_version = null, $access_token = null, $token_expiry = null)
    {
        if (!empty($cookies)) {
            $this->cookies = $cookies;
        }
        if (!empty($access_token)) {
            $this->v3_access_token = $access_token;
        }
        if (!empty($token_expiry)) {
            $this->v3_access_token_expires_at = $token_expiry;
        }

        if (empty($root_server_version)) {
            $version = \get_option('bmltwf_bmlt_server_version', false);
            if (!$version) {
                \update_option("bmltwf_bmlt_server_version", $this->bmltwf_get_remote_server_version(\get_option('bmltwf_bmlt_server_address')));
            }
            $this->bmlt_root_server_version = $version;
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

    public function update_root_server_version()
    {
        $new_version = $this->bmltwf_get_remote_server_version(\get_option('bmltwf_bmlt_server_address'));
        $this->bmlt_root_server_version = $new_version;
        \update_option("bmltwf_bmlt_server_version", $new_version);
    }

    private function bmltwf_integration_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('bmltwf_error', $message, $data);
    }

    // public function convertv3meetingtov2($meeting)
    // {
    //     $fromto = array();
    //     $fromto['serviceBodyId'] = 'serviceBodyId';
    //     $fromto['venueType'] = 'venueType';
    //     $fromto['day'] = 'day';
    //     $fromto['name'] = 'name';

    //     // change all our fields over
    //     foreach ($fromto as $from => $to) {
    //         $here = $meeting[$from] ?? false;
    //         if ($here) {
    //             $meeting[$to] = $meeting[$from];
    //             unset($meeting[$from]);
    //         }
    //     }

    //     // special cases
    //     $here = $meeting['duration'] ?? false;
    //     if ($here) {
    //         $meeting['duration'] = $meeting['duration'] . ":00";
    //         unset($meeting['duration']);
    //     }

    //     $here = $meeting['startTime'] ?? false;
    //     if ($here) {
    //         $meeting['startTime'] = $meeting['startTime'] . ":00";
    //         unset($meeting['startTime']);
    //     }

    //     $here = $meeting['formatIds'] ?? false;
    //     if ($here) {
    //         $meeting['formatIds'] = implode(',', $meeting['formatIds']);
    //         unset($meeting['formatIds']);
    //     }

    //     // day starts at 0 for BMLT 3.x
    //     $here = $meeting['day'] ?? false;
    //     if ($here) {
    //         $meeting['day'] = $meeting['day'] + 1;
    //         unset($meeting['day']);
    //     }

    //     return $meeting;
    // }

    // private function convertv2meetingtov3($meeting)
    // {
    //     $fromto = array();
    //     $fromto['serviceBodyId'] = 'serviceBodyId';
    //     $fromto['venueType'] = 'venueType';
    //     $fromto['day'] = 'day';
    //     $fromto['name'] = 'name';
    //     $fromto['worldid_mixed'] = 'worldId';

    //     // dont need this any more
    //     unset($meeting['id_bigint']);

    //     // change all our fields over
    //     foreach ($fromto as $from => $to) {
    //         $here = $meeting[$from] ?? false;
    //         if ($here) {
    //             $meeting[$to] = $meeting[$from];
    //             unset($meeting[$from]);
    //         }
    //     }

    //     // special cases
    //     $here = $meeting['duration'] ?? false;
    //     if ($here) {
    //         $time = explode(':', $meeting['duration']);
    //         $meeting['duration'] = $time[0] . ":" . $time[1];
    //         unset($meeting['duration']);
    //     }

    //     $here = $meeting['startTime'] ?? false;
    //     if ($here) {
    //         $time = explode(':', $meeting['startTime']);
    //         $meeting['startTime'] = $time[0] . ":" . $time[1];
    //         unset($meeting['startTime']);
    //     }

    //     $here = $meeting['formatIds'] ?? false;
    //     if ($here) {
    //         $meeting['formatIds'] = array_map('intval', explode(',', $meeting['formatIds']));
    //         // $meeting['formatIds'] = explode(',', $meeting['formatIds']);
    //         unset($meeting['formatIds']);
    //     } else
    //     // if we dont even have a format list, then v3 requires at least a blank array here
    //     {
    //         $meeting['formatIds'] = [];
    //     }

    //     $this->debug_log($meeting);
    //     // venue type can't be a 4 for BMLT 3.x #161
    //     $here = $meeting['venueType'] ?? false;
    //     $this->debug_log(gettype($here));
    //     if ($here && $here === 4) {
    //         $meeting['venueType'] = 2;
    //         $meeting['temporarilyVirtual'] = true;
    //     }

    //     // day starts at 0 for BMLT 3.x
    //     $here = $meeting['day'] ?? false;
    //     if ($here) {
    //         $meeting['day'] = $meeting['day'] - 1;
    //     }

    //     return $meeting;
    // }

    public function is_supported_server($server)
    {
        $version = $this->bmltwf_get_remote_server_version($server);
        if (version_compare($version, "3.0.0-rc0", "lt")) {
            $this->debug_log("unsupported server version");
            return false;
        } else {
            $this->debug_log("supported server version");
            return true;
        }
    }

    public function bmltwf_get_remote_server_version($server)
    {
        $url = $server . "client_interface/serverInfo.xml";
        // $this->debug_log("url = " . $url);
        $headers = array(
            "Accept: */*",
        );

        $resp = wp_remote_get($url, array('headers' => $headers));
        // $this->debug_log("wp_remote_get returns " . \wp_remote_retrieve_response_code($resp));
        // $this->debug_log(\wp_remote_retrieve_body($resp));

        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) != 200) {
            return false;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(\wp_remote_retrieve_body($resp));
        if ($xml === false) {
            return false;
        } else {
            if (!($xml->serverVersion->readableString instanceof \SimpleXMLElement)) {
                return false;
            }
            $version = $xml->serverVersion->readableString->__toString();
            $this->debug_log("version returns = " . $version);
            return $version;
        }
    }

    // /**
    //  * retrieve_single_meeting
    //  *
    //  * @param  int $meeting_id
    //  * @return void
    //  */
    // public function retrieve_single_meeting($meeting_id)
    // {

    //     $bmltwf_bmlt_server_address = get_option('bmltwf_bmlt_server_address');

    //     $url = $bmltwf_bmlt_server_address . "client_interface/json/?switcher=GetSearchResults&advanced_published=0&meeting_key=id_bigint&lang_enum=en&meeting_key_value=" . $meeting_id;
    //     $headers = array(
    //         "Accept: */*",
    //     );
    //     $this->debug_log("wp_remote_get from url " . $url);

    //     $resp = wp_remote_get($url, array('headers' => $headers));
    //     $this->debug_log("wp_remote_get returns " . \wp_remote_retrieve_response_code($resp));
    //     $this->debug_log(\wp_remote_retrieve_body($resp));

    //     if ((!is_array($resp)) ||  is_wp_error($resp)) {
    //         return $this->bmltwf_integration_error(__('Server error retrieving meeting', 'bmlt-workflow'), 500);
    //     }

    //     $body = wp_remote_retrieve_body($resp);

    //     $meetingarr = json_decode($body, true);
    //     if (empty($meetingarr[0])) {
    //         return $this->bmltwf_integration_error(__('Server error retrieving meeting', 'bmlt-workflow'), 500);
    //     }
    //     $meeting = $meetingarr[0];
    //     $this->debug_log("SINGLE MEETING");
    //     $this->debug_log(($meeting));
    //     return $meeting;
    // }

    public function testServerAndAuth($username, $password, $server)
    {

        $postargs = array(
            'username' => $username,
            'password' => $password
        );

        $url = $server . "api/v1/auth/token";
        $this->debug_log($url);
        $response = \wp_remote_post($url, array('body' => http_build_query($postargs)));
        // $this->debug_log("wp_remote_post returns " . \wp_remote_retrieve_response_code($response));
        // $this->debug_log(\wp_remote_retrieve_body($response));

        if (is_wp_error($response)) {
            return new \WP_Error('bmltwf', __('check BMLT server address', 'bmlt-workflow'));
        }

        $response_code = \wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            return new \WP_Error('bmltwf', __('check BMLT server address', 'bmlt-workflow'));
        }

        $auth_details = json_decode(\wp_remote_retrieve_body($response), true);
        if (!$auth_details || !isset($auth_details['access_token'])) {
            return new \WP_Error('bmltwf', __('Invalid authentication response', 'bmlt-workflow'));
        }
        // $this->debug_log($auth_details['access_token']);

        return true;
    }

    function updateMeeting($change)
    {
        $this->debug_log("updateMeeting change");
        $this->debug_log($change);
        if (array_key_exists('formatIds', $change)) {
            $change['formatIds'] = $this->removeLocations($change['formatIds']);
        }

        $this->debug_log("inside updateMeetingv3 auth");

        if (!array_key_exists('id', $change)) {
            return new \WP_Error('bmltwf', 'updateMeetingv3: No meeting ID present');
        }

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting updateMeetingv3 authenticateRootServer failed");
                return $ret;
            }
        }

        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings/' . $change['id'];

        $this->debug_bmlt_payload($url, 'PATCH', $change);
        
        $response = \wp_remote_request($url, array(
            'method' => 'PATCH',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->v3_access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($change),
            'timeout' => 60
        ));

        $this->debug_log("v3 wp_remote_request returns " . \wp_remote_retrieve_response_code($response));
        $this->debug_log(\wp_remote_retrieve_body($response));

        if (\wp_remote_retrieve_response_code($response) != 204) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        return true;
    }

    public function getMeeting($meeting_id)
    {

        $this->debug_log("inside getMeeting auth");

        if (!$meeting_id) {
            return new \WP_Error('bmltwf', 'getMeeting: No meeting ID present');
        }

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting getMeeting authenticateRootServer failed");
                return $ret;
            }
        }
        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings/' . $meeting_id;

        $response = \wp_remote_request($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token), 'GET'));
        $this->debug_log("v3 wp_remote_request returns " . \wp_remote_retrieve_response_code($response));
        $this->debug_log(\wp_remote_retrieve_body($response));

        if (is_wp_error($response)) {
            return $response;
        }

        if (\wp_remote_retrieve_response_code($response) != 200) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        $result = json_decode(\wp_remote_retrieve_body($response),true);
        if ($result === null) {
            return new \WP_Error('bmltwf', 'Invalid JSON response from BMLT server');
        }
        return $result;
    }

    public function getAllMeetings()
    {
        $service_bodies = implode(',', array_keys($this->getServiceBodies()));
        $this->debug_log("service_bodies");
        $this->debug_log($service_bodies);
        $this->debug_log("inside getMeeting auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting getMeeting authenticateRootServer failed");
                return $ret;
            }
        }
        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings';
        $url = add_query_arg(array('serviceBodyIds' => $service_bodies), $url);
        $response = \wp_remote_request($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token), 'GET'));
        $this->debug_log("v3 wp_remote_request returns " . \wp_remote_retrieve_response_code($response));
        $this->debug_log(\wp_remote_retrieve_body($response));

        if (is_wp_error($response)) {
            return $response;
        }

        if (\wp_remote_retrieve_response_code($response) != 200) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        $permitted_fields = [
            'id',
            'serviceBodyId',
            'name',
            'day',
            'startTime',
            'duration',
            'venueType',
            'location_text',
            'location_street',
            'location_info',
            'location_municipality',
            'location_province',
            'location_sub_province',
            'location_nation',
            'location_postal_code_1',
            'formatIds',
            'virtual_meeting_link',
            'virtual_meeting_additional_info',
            'phone_meeting_number',
            'published',
            'latitude',
            'longitude'
        ];
        
        $body = json_decode(\wp_remote_retrieve_body($response),true);
        if ($body === null) {
            return new \WP_Error('bmltwf', 'Invalid JSON response from BMLT server');
        }
        foreach ($body as &$meeting) {
            foreach ($meeting as $field => $value) {
                if (!in_array($field, $permitted_fields)) {
                    unset($meeting[$field]);
                }
            }
        }

        return json_encode($body);
    }

    function getServiceBodies()
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

        $this->debug_bmlt_payload($url);

        $response = \wp_remote_get($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token)));
        // $this->debug_log("v3 wp_remote_get returns " . \wp_remote_retrieve_response_code($response));
        // $this->debug_log(\wp_remote_retrieve_body($response));

        if (is_wp_error($response)) {
            return $response;
        }

        if (\wp_remote_retrieve_response_code($response) != 200) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }

        $response_data = json_decode(\wp_remote_retrieve_body($response), 1);
        if ($response_data === null) {
            return new \WP_Error('bmltwf', 'Invalid JSON response from BMLT server');
        }
        $sblist = [];

        foreach ($response_data as $key => $sb) {
            if (isset($sb['id'], $sb['name'], $sb['description'])) {
                $sblist[$sb['id']] = array('name' => $sb['name'], 'description' => $sb['description']);
            }
        }
        if (!$sblist) {
            return new \WP_Error('bmltwf', "No service bodies available - does your workflow user have access to any service bodies within BMLT?");
        }

        return $sblist;
    }

    function deleteMeeting($meeting_id)
    {
        $this->debug_log("inside deleteMeeting v3 auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
        }

        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings/' . $meeting_id;

        $this->debug_bmlt_payload($url, 'DELETE');

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
        // $this->debug_log("get permissions response returns " . \wp_remote_retrieve_response_code($response));
        // $this->debug_log(\wp_remote_retrieve_body($response));
        if (is_wp_error($response)) {

            return $this->bmltwf_integration_error(__('BMLT Root Server Communication Error - Check the BMLT Root Server configuration settings', 'bmlt-workflow'), 500);
        }

        $arr = json_decode(\wp_remote_retrieve_body($response), 1);

        if ((!is_array($arr)) || (!array_key_exists('service_body', $arr))) {
            return $this->bmltwf_integration_error(__('BMLT Root Server Communication Error - Cannot retrieve service bodies', 'bmlt-workflow'), 500);
        }

        // if this is just a single service body, then fix the array up
        if (!array_key_exists('0', $arr['service_body'])) {
            $newarr = array();
            $newarr['service_body'] = array();
            $newarr['service_body'][0] = $arr['service_body'];
            $arr = $newarr;
        }

        return $arr;
    }

    private function removeLocations(array $format): array
    {
        // Remove virtual  meeting, temporarily closed, hybrid formats as these are handled seperately
        $removableLocations = ["VM", "TC", "HY"];
        $realformats = $this->getMeetingFormats();
        $newformats = array();
        foreach ($format as $key => $value) {
            if (!in_array($realformats[$value]['key_string'], $removableLocations)) {
                $newformats[] = $value;
            }
        }
        return $newformats;
    }

    private $bmlt_langmap = array(
        "en_EN" => "en",
        "fr_FR" => "fr",
        "de_DE" => "de",
        "dk_DK" => "dk",
        "es_ES" => "es",
        "fa_FA" => "fa",
        "it_IT" => "it",
        "pl_PL" => "pl",
        "pt_PT" => "pt",
        "ru_RU" => "ru",
        "sv_SE" => "sv",
    );

    public function wp_locale_to_bmlt_locale()
    {
        $locale = get_locale();
        // default language is english
        $ourlang = 'en';
        if (array_key_exists($locale, $this->bmlt_langmap)) {
            $ourlang = $this->bmlt_langmap[$locale];
        }
        return $ourlang;
    }

    public function getMeetingFormats()
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

        $this->debug_bmlt_payload($url);

        $ret = \wp_remote_get($url, $args);
        // $this->debug_log("body");
        // $this->debug_log(\wp_remote_retrieve_body($ret));
        $formatarr = json_decode(\wp_remote_retrieve_body($ret), 1);
        // $this->debug_log("FORMATARR");
        // $this->debug_log($formatarr);

        $ourlang = $this->wp_locale_to_bmlt_locale();
        $newformat = array();
        foreach ($formatarr as $key => $value) {

            $newvalue = array();

            $newvalue['world_id'] = $value['worldId'];
            $newvalue['type'] = $value['type'];

            foreach ($value['translations'] as $key1 => $value1) {

                if ($value1['language'] === 'en' && (!(array_key_exists('lang', $newvalue)))) {
                    $newvalue['lang'] = 'en';
                    $newvalue['description_string'] = $value1['description'];
                    $newvalue['name_string'] = $value1['name'];
                    $newvalue['key_string'] = $value1['key'];
                } elseif ($value1['language'] === $ourlang) {
                    $newvalue['lang'] = $ourlang;
                    $newvalue['description_string'] = $value1['description'];
                    $newvalue['name_string'] = $value1['name'];
                    $newvalue['key_string'] = $value1['key'];
                }
            }
            if (array_key_exists('lang', $newvalue)) {
                $formatid = $value['id'];
                $newformat[$formatid] = $newvalue;
            }
        }
        $this->debug_log("NEWFORMAT size " . count($newformat));
        // $this->debug_log($newformat);

        return $newformat;
    }


    public function is_valid_bmlt_server($server)
    {
        $response = \wp_remote_get($server . 'client_interface/json/?switcher=GetServerInfo');

        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return false;
        }
        return true;
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
            return new \WP_Error('bmltwf', __('BMLT Configuration Error - Unable to retrieve server info', 'bmlt-workflow'));
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true);
        if ($arr === null || !isset($arr[0])) {
            return new \WP_Error('bmltwf', __('Invalid server info response', 'bmlt-workflow'));
        }
        if (!empty($arr[0]['meeting_states_and_provinces'])) {
            $states = explode(',', $arr[0]['meeting_states_and_provinces']);
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
        // $this->debug_log("getMeetingCounties returns " . \wp_remote_retrieve_response_code($response));
        // $this->debug_log(\wp_remote_retrieve_body($response));

        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', __('BMLT Configuration Error - Unable to retrieve server info', 'bmlt-workflow'));
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true);
        if ($arr === null || !isset($arr[0])) {
            return new \WP_Error('bmltwf', __('Invalid server info response', 'bmlt-workflow'));
        }
        // 
        // $this->debug_log("***");
        // $this->debug_log(($arr));
        if (!empty($arr[0]['meeting_counties_and_sub_provinces'])) {
            $counties = explode(',', $arr[0]['meeting_counties_and_sub_provinces']);
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

        $storedkey = get_option('bmltwf_google_maps_key', '');

        if ($storedkey != '') {
            $this->debug_log("returning saved google maps key with length " . strval(strlen($storedkey)));
            return $storedkey;
        }

        $storedkey = get_option('bmltwf_bmlt_google_maps_key', '');

        if ($storedkey != '') {
            $this->debug_log("returning bmlt google maps key with length " . strval(strlen($storedkey)));
            return $storedkey;
        }

        // force v2 usage because we're authing and scraping the web page
        $ret = $this->authenticateRootServerv2();
        if (is_wp_error($ret)) {
            $this->debug_log("*** AUTH ERROR");
            $this->debug_log(($ret));
            return $ret;
        }

        $url = get_option('bmltwf_bmlt_server_address') . "index.php";
        $this->debug_log("*** ADMIN URL " . $url);

        $response = $this->getv2($url, $this->cookies);

        preg_match('/"google_api_key":"(.*?)",/', \wp_remote_retrieve_body($response), $matches);
        $this->debug_log("retrieved gmaps key");
        $gmaps_key = $matches[1];

        \update_option('bmltwf_bmlt_google_maps_key', $gmaps_key);

        return $gmaps_key;
    }


    function createMeeting($meeting)
    {
        $this->debug_log("createMeeting change");
        $this->debug_log($meeting);

        $this->debug_log("json");
        $this->debug_log(json_encode($meeting));

        $this->debug_log("formatsIds before");
        $this->debug_log($meeting['formatIds']);

        $meeting['formatIds'] = $this->removeLocations($meeting['formatIds']);

        $this->debug_log("formatsIds after");
        $this->debug_log($meeting['formatIds']);

        $this->debug_log("inside createMeetingv3 auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                $this->debug_log("exiting createMeetingv3 authenticateRootServer failed");
                return $ret;
            }
        }
        $url = get_option('bmltwf_bmlt_server_address') . 'api/v1/meetings';

        $this->debug_bmlt_payload($url, 'POST', $meeting);

        $response = \wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->v3_access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($meeting),
            'timeout' => 60
        ));

        $this->debug_log("v3 wp_remote_post returns " . \wp_remote_retrieve_response_code($response));
        $this->debug_log(\wp_remote_retrieve_body($response));

        if (\wp_remote_retrieve_response_code($response) != 201) {
            return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
        }
        return true;
    }
    function isAutoGeocodingEnabled($geocoding_type)
    {

        $this->debug_log("auto geocoding " . $geocoding_type);

        if ((empty($geocoding_type) || ($geocoding_type !== 'auto') && ($geocoding_type !== 'county') && ($geocoding_type !== 'zip'))) {
            $this->debug_log("auto geocoding check contains invalid geocoding type");
            return false;
        }

        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');
        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', __('BMLT Configuration Error - Unable to retrieve server info', 'bmlt-workflow'));
        }
        $arr = json_decode(\wp_remote_retrieve_body($response), true);
        if ($arr === null || !isset($arr[0])) {
            return new \WP_Error('bmltwf', __('Invalid server info response', 'bmlt-workflow'));
        }
        $search = '';

        switch ($geocoding_type) {
            case 'auto':
                $search = 'auto_geocoding_enabled';
                break;
            case 'zip':
                $search = 'zip_auto_geocoding_enabled';
                break;
            case 'county':
                $search = 'county_auto_geocoding_enabled';
                break;
        }

        if (!empty($arr[0][$search])) {
            return $arr[0][$search];
        }
        return false;
    }

    public function getDefaultLatLong()
    {
        $response = \wp_remote_get(\get_option('bmltwf_bmlt_server_address') . 'client_interface/json/?switcher=GetServerInfo');

        if (is_wp_error($response) || (\wp_remote_retrieve_response_code($response) != 200)) {
            return new \WP_Error('bmltwf', __('BMLT Configuration Error - Unable to retrieve server info', 'bmlt-workflow'));
        }
        // $this->debug_log(\wp_remote_retrieve_body($response));  
        $arr = json_decode(\wp_remote_retrieve_body($response), true);
        if ($arr === null || !isset($arr[0])) {
            return new \WP_Error('bmltwf', __('Invalid server info response', 'bmlt-workflow'));
        }
        if ((!empty($arr[0]['centerLongitude'])) && (!empty($arr[0]['centerLatitude']))) {

            return array('longitude' => $arr[0]['centerLongitude'], 'latitude' => $arr[0]['centerLatitude']);
        }
        return false;
    }

    public function geolocateAddress($address)
    {
        $key = $this->getGmapsKey();
        if (\is_wp_error($key)) {
            return $this->bmltwf_integration_error(__('Server error geolocating address: Could not retrieve google maps key.', 'bmlt-workflow'), 500);
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $key;

        $this->debug_log("*** GMAPS URL");
        $this->debug_log($url);

        $headers = array(
            "Accept: */*",
        );

        $resp = \wp_remote_get($url, array('headers' => $headers));

        if ((!is_array($resp)) ||  is_wp_error($resp)) {
            return $this->bmltwf_integration_error(__('Server error geolocating address: Could not perform google maps lookup', 'bmlt-workflow'), 500);
        }

        $body = \wp_remote_retrieve_body($resp);

        if (!$body) {
            return $this->bmltwf_integration_error(__('Server error geolocating address: Nothing returned from google maps lookup', 'bmlt-workflow'), 500);
        }

        $this->debug_log("*** GMAPS RESPONSE");
        $this->debug_log($body);

        $geo = json_decode($body, true);
        if ((empty($geo)) || (empty($geo['status']))) {
            return $this->bmltwf_integration_error(__('Server error geolocating address: No google maps status code returned', 'bmlt-workflow'), 500);
        }
        if ($geo['status'] === "REQUEST_DENIED") {
            return $this->bmltwf_integration_error(__('Server error geolocating address', 'bmlt-workflow') . ': ' . $geo['error_message'], 500);
        }

        if (($geo['status'] === "ZERO_RESULTS") || empty($geo['results'][0]['geometry']['location']['lat']) || empty($geo['results'][0]['geometry']['location']['lng'])) {
            return new \WP_Error('bmltwf', __('Could not geolocate meeting address. Please try amending the address with additional/correct details.', 'bmlt-workflow'));
        } else {
            // $location = array();
            // $location['latitude'] = $geo['results'][0]['geometry']['location']['lat'];
            // $location['longitude'] = $geo['results'][0]['geometry']['location']['lng'];
            return $geo;
        }
    }

    private function secrets_decrypt($password, $data)
    {
        // $this->debug_log($data);
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
        if (!$this->v3_access_token || $this->v3_access_token_expires_at < time()) {
            return false;
        } else {
            return true;
        }
    }

    private function decodeBMLTPassword()
    {
        $encrypted = get_option('bmltwf_bmlt_password');
        $this->debug_log("retrieved encrypted bmlt password");

        if ($encrypted === false) {
            return new \WP_Error('bmltwf', __('Error unpacking password.', 'bmlt-workflow'));
        }

        if (defined('BMLTWF_RUNNING_UNDER_PHPUNIT')) {
            $nonce_salt = BMLTWF_PHPUNIT_NONCE_SALT;
        } else {
            $nonce_salt = NONCE_SALT;
        }

        $decrypted = $this->secrets_decrypt($nonce_salt, $encrypted);
        if ($decrypted === false) {
            return new \WP_Error('bmltwf', __('Error decrypting password.', 'bmlt-workflow'));
        }
        return $decrypted;
    }

    function authenticateRootServer()
    {
        $decrypted = $this->decodeBMLTPassword();
        if (\is_wp_error($decrypted)) {
            return $decrypted;
        }

        if (!$this->is_v3_token_valid()) {

            $postargs = array(
                'username' => get_option('bmltwf_bmlt_username'),
                'password' => $decrypted
            );
            $this->debug_log("inside authenticateRootServer v3 auth");
            $url = get_option('bmltwf_bmlt_server_address') . "api/v1/auth/token";
            $response = \wp_remote_post($url, array('body' => http_build_query($postargs)));
            
            if (is_wp_error($response)) {
                return $response;
            }

            $response_code = \wp_remote_retrieve_response_code($response);

            if ($response_code != 200) {
                return new \WP_Error('bmltwf', \wp_remote_retrieve_response_message($response));
            }

            $auth_details = json_decode(\wp_remote_retrieve_body($response), true);
            if (!$auth_details || !isset($auth_details['access_token'], $auth_details['expires_at'])) {
                return new \WP_Error('bmltwf', 'Invalid authentication response from BMLT server');
            }
            $this->v3_access_token = $auth_details['access_token'];
            $this->v3_access_token_expires_at = $auth_details['expires_at'];
        }
        return true;
    }

    function authenticateRootServerv2()
    {

        $decrypted = $this->decodeBMLTPassword();
        if (\is_wp_error($decrypted)) {
            return $decrypted;
        }

        // legacy auth
        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => get_option('bmltwf_bmlt_username'),
            'c_comdef_admin_password' => $decrypted
        );
        $url = get_option('bmltwf_bmlt_server_address') . "index.php";

        $this->debug_bmlt_payload($url, $method = 'POST', $body = '(login payload)');

        $ret = \wp_remote_post($url, $this->set_args(null, http_build_query($postargs)));
        // $this->debug_log("returns");
        // $this->debug_log($ret);

        if ((is_wp_error($ret)) || (\wp_remote_retrieve_response_code($ret) != 200)) {
            return new \WP_Error('bmltwf', __('authenticateRootServer: Server Failure', 'bmlt-workflow'));
        }
        $body = wp_remote_retrieve_body($ret);
        // $this->debug_log("BODY");
        // $this->debug_log($body);
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', $body)) // best way I could find to check for invalid login
        {
            $this->cookies = null;
            return new \WP_Error('bmltwf', __('authenticateRootServer: Authentication Failure', 'bmlt-workflow'));
        }
        $a = \wp_remote_retrieve_cookie($ret, 'PHPSESSID');
        $this->debug_log("setting cookies");
        $this->debug_log($a);
        // $this->cookies = \wp_remote_retrieve_cookie($ret,'PHPSESSID');
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

        $args = array(
            'timeout' => '120',
            'headers' => $newheaders,
            'cookies' => isset($cookies) ? $cookies : null,
            'body' => isset($body) ? $body : null
        );
        if ($method) {
            $args['method'] = $method;
        }

        return $args;
    }

    private function getv3($url)
    {
        $this->debug_log("inside get v3");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
        }
        $this->debug_bmlt_payload($url, $method = null);

        $ret = \wp_remote_get($url, $this->set_args(null, null, array("Authorization" => "Bearer " . $this->v3_access_token)));
        return $ret;
    }

    private function getv2($url, $cookies = null)
    {
        $this->debug_log("inside get v2");

        $this->debug_bmlt_payload($url);

        $ret = \wp_remote_get($url, $this->set_args($cookies));
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', \wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
            // try once more in case it was a session timeout
            $this->debug_bmlt_payload($url);

            $ret = wp_remote_get($url, $this->set_args($cookies));
        }
        return $ret;
    }

    private function post($url, $postargs, $cookies = null)
    {
        $this->debug_log("inside post v3 auth");

        if (!$this->is_v3_token_valid()) {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
        }

        $this->debug_bmlt_payload($url, 'POST', http_build_query($postargs));

        $ret = \wp_remote_post($url, $this->set_args(null, http_build_query($postargs), array("Authorization" => "Bearer " . $this->v3_access_token), 'POST'));
        return $ret;
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

}
<?php
namespace wbw\BMLT;

use wbw\Debug;
use wbw\REST\HandlerCore;
class Integration
{
    protected $cookies = null; // our authentication cookies
    
    public function __construct($cookies = null)
    {
        if (!empty($cookies))
        {
            $this->cookies = $cookies;
        }

    }

    private function wbw_rest_error($message, $code)
    {
        return new \WP_Error('wbw_error', $message, array('status' => $code));
    }
    

    // accepts raw string or array
    private function wbw_rest_success($message)
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

    private function wbw_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('wbw_error', $message, $data);
    }

    /**
     * retrieve_single_meeting
     *
     * @param  int $meeting_id
     * @return void
     */
    public function retrieve_single_meeting($meeting_id)
    {
        global $wbw_dbg;

        $wbw_bmlt_server_address = get_option('wbw_bmlt_server_address');
        $url = $wbw_bmlt_server_address . "/client_interface/json/?switcher=GetSearchResults&meeting_key=id_bigint&lang_enum=en&meeting_key_value=" . $meeting_id;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: */*",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        if (!$resp) {
            return $this->wbw_rest_error('Server error retrieving meeting', 500);
        }
        curl_close($curl);
        $meetingarr = json_decode($resp, true);
        if (empty($meetingarr[0])) {
            return $this->wbw_rest_error('Server error retrieving meeting', 500);
        }
        $meeting = $meetingarr[0];
        $wbw_dbg->debug_log("SINGLE MEETING");
        $wbw_dbg->debug_log($wbw_dbg->vdump($meeting));
        // how possibly can we get a meeting that is not the same as we asked for
        if ($meeting['id_bigint'] != $meeting_id) {
            return $this->wbw_rest_error('Server error retrieving meeting', 500);
        }
        return $meeting;
    }

    public function testServerAndAuth($username, $password, $server)
    {
        global $wbw_dbg;
        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => $username,
            'c_comdef_admin_password' => $password
        );

        $url = $server . "index.php";
        $wbw_dbg->debug_log($url);
        $ret = \wp_safe_remote_post($url, array('body'=> http_build_query($postargs)));
        $wbw_dbg->debug_log($wbw_dbg->vdump($ret));

        $response_code = \wp_remote_retrieve_response_code($ret);

        if ($response_code != 200)
        {
            return new \WP_Error('wbw', 'check BMLT server address');
        }
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            return new \WP_Error('wbw', 'check username and password details');
        }
        return true;
    }

    public function getMeetingFormats()
    {
        global $wbw_dbg;

        $req = array();
        $req['admin_action'] = 'get_format_info';

        // get an xml for a workaround
        $response = $this->postAuthenticatedRootServerRequestSemantic('local_server/server_admin/xml.php', $req);
        if (is_wp_error($response)) {
            return new \WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }

        // $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetFormats', array());
        // if (is_wp_error($response)) {
        //     return new \WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        // }

        $wbw_dbg->debug_log(wp_remote_retrieve_body($response));  
        // $formatarr = json_decode(wp_remote_retrieve_body($response), true);
        $xml = simplexml_load_string(wp_remote_retrieve_body($response));
        $wbw_dbg->debug_log("XML RESPONSE");
        $wbw_dbg->debug_log(wp_remote_retrieve_body($response));
        $formatarr = json_decode(json_encode($xml), 1);

        $wbw_dbg->debug_log($wbw_dbg->vdump($formatarr));

        $newformat = array();
        foreach ($formatarr['row'] as $key => $value) {
            $formatid = $value['id'];
            unset($value['id']);
            $newformat[$formatid] = $value;            
        }
        $wbw_dbg->debug_log("NEWFORMAT");
        $wbw_dbg->debug_log($wbw_dbg->vdump($newformat));

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
            return new \WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // $wbw_dbg->debug_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        if(!empty($arr['meeting_states_and_provinces']))
        {
            $states = explode(',',$arr['meeting_states_and_provinces']);
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
            return new \WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // $wbw_dbg->debug_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        // global $wbw_dbg;
        // $wbw_dbg->debug_log("***");
        // $wbw_dbg->debug_log($wbw_dbg->vdump($arr));
        if(!empty($arr['meeting_counties_and_sub_provinces']))
        {
            $counties = explode(',',$arr['meeting_counties_and_sub_provinces']);
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
        global $wbw_dbg;
        $ret = $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            // $wbw_dbg->debug_log("*** AUTH ERROR");
            // $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
            return $ret;
        }

        $url = \get_option('wbw_bmlt_server_address') . "index.php";
        // $wbw_dbg->debug_log("*** ADMIN URL ".$url);

        $resp = $this->get($url, $this->cookies);
        // $wbw_dbg->debug_log("*** ADMIN PAGE");
        // $wbw_dbg->debug_log(wp_remote_retrieve_body($resp));

        preg_match('/"google_api_key":"(.*?)",/', wp_remote_retrieve_body($resp), $matches);
        return $matches[1];
    }

    public function geolocateAddress($address)
    {
        $key = $this->getGmapsKey();

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".$key;
        global $wbw_dbg;
        $wbw_dbg->debug_log("*** GMAPS URL");
        $wbw_dbg->debug_log($url);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: */*",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);

        if (!$resp) {
            return new \WP_Error('wbw', 'Server error geolocating address');
        }

        curl_close($curl);
        $wbw_dbg->debug_log("*** GMAPS RESPONSE");
        $wbw_dbg->debug_log($resp);

        $geo = json_decode($resp, true);
        if((empty($geo)) || (empty($geo['status'])))
        {
            return new \WP_Error('wbw', 'Server error geolocating address');
        }
        if(($geo['status'] === "ZERO_RESULTS") || empty($geo['results'][0]['geometry']['location']['lat']) || empty($geo['results'][0]['geometry']['location']['lng']))
        {
            return new \WP_Error('wbw', 'Could not geolocate meeting address. Please try amending the address with additional/correct details.');
        }
        else
        {
            $location = array();
            $location['latitude'] = $geo['results'][0]['geometry']['location']['lat'];
            $location['longitude'] = $geo['results'][0]['geometry']['location']['lng'];
            return $location;
        }
    }

    private function authenticateRootServer()
    {
        if ($this->cookies == null) {
            $postargs = array(
                'admin_action' => 'login',
                'c_comdef_admin_login' => \get_option('wbw_bmlt_username'),
                'c_comdef_admin_password' => \get_option('wbw_bmlt_password')
            );
            $url = \get_option('wbw_bmlt_server_address') . "index.php";

            // $wbw_dbg->debug_log("AUTH URL = " . $url);
            $ret = $this->post($url, null, $postargs);

            if (is_wp_error($ret))
            {
                return new \WP_Error('wbw', 'authenticateRootServer: Server Failure');
            }  

            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $this->cookies = null;
                return new \WP_Error('wbw', 'authenticateRootServer: Authentication Failure');
            }
            
            $this->cookies = \wp_remote_retrieve_cookies($ret);

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

    private function post($url, $cookies = null, $postargs)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("POSTING URL = " . $url);
        // $wbw_dbg->debug_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        // $wbw_dbg->debug_log("*********");
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

    private function postsemantic($url, $cookies = null, $postargs)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("POSTING SEMANTIC URL = " . $url);
        // $wbw_dbg->debug_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        // $wbw_dbg->debug_log("*********");
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
            $wbw_dbg->debug_log("our post body is " . $newargs);
            $ret = \wp_safe_remote_post($url, $this->set_args($cookies, $newargs));
            $wbw_dbg->debug_log($wbw_dbg->vdump($ret));
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
        if (!(is_array($postargs)))
        {
            return $this->wbw_rest_error("Missing post parameters", "wbw_bmlt_integration");
        }
        return $this->post(\get_option('wbw_bmlt_server_address') . $url, $this->cookies, $postargs);
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
        if (!(is_array($postargs)))
        {
            return $this->wbw_rest_error("Missing post parameters", "wbw_bmlt_integration");
        }
        return $this->post(\get_option('wbw_bmlt_server_address') . $url, null, $postargs);
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
        if (!(is_array($postargs)))
        {
            return $this->wbw_rest_error("Missing post parameters", "wbw_bmlt_integration");
        }

        return $this->postsemantic(\get_option('wbw_bmlt_server_address') . $url, $this->cookies, $postargs);
    }

}

?>


<?php

if (!defined('ABSPATH')) exit; // die if being called directly

class BMLTIntegration
{
    protected $cookies = null; // our authentication cookies

    public function testServerAndAuth($username, $password, $server)
    {
        $postargs = array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => $username,
            'c_comdef_admin_password' => $password
        );

        $url = $server . "index.php";

        $ret = wp_safe_remote_post($url, array('body'=> http_build_query($postargs)));
        // error_log(vdump($ret));

        $response_code = wp_remote_retrieve_response_code($ret);

        if ($response_code != 200)
        {
            return new WP_Error('wbw', 'check BMLT server address');
        }
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            return new WP_Error('wbw', 'check username and password details');
        }
        return true;
    }

    public function getMeetingFormats()
    {
        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetFormats', array());
        if (is_wp_error($response)) {
            return new WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        error_log(wp_remote_retrieve_body($response));  
        $formatarr = json_decode(wp_remote_retrieve_body($response), true)[0];
        error_log(vdump($formatarr));
        $newformat = array();
        foreach ($formatarr as $key => $value) {
            foreach ($value as $key2 => $value2) {
                // handle blank values
                if (is_array($value2)) {
                    $value[$key2] = '';
                }
            }
            $newformat[$formatarr[$key]['id']] = $value;
        }
        return $newformat;
    }

    public function getMeetingStates()
    {
        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        if (is_wp_error($response)) {
            return new WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // error_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        if(!empty($arr['meeting_states_and_provinces']))
        {
            $states = explode(',',$arr['meeting_states_and_provinces']);
            return $states;
        }
        return false;
    }

    public function getMeetingCounties()
    {
        $response = $this->postUnauthenticatedRootServerRequest('client_interface/json/?switcher=GetServerInfo', array());
        if (is_wp_error($response)) {
            return new WP_Error('wbw','BMLT Configuration Error - Unable to retrieve meeting formats');
        }
        // error_log(wp_remote_retrieve_body($response));  
        $arr = json_decode(wp_remote_retrieve_body($response), true)[0];
        if(!empty($arr['meeting_counties_and_sub_provinces']))
        {
            $counties = explode(',',$arr['meeting_counties_and_sub_provinces']);
            return $counties;
        }
        return false;

    }

    private function vdump($object)
    {
        ob_start();
        var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    private function authenticateRootServer()
    {
        if ($this->cookies == null) {
            $postargs = array(
                'admin_action' => 'login',
                'c_comdef_admin_login' => get_option('wbw_bmlt_username'),
                'c_comdef_admin_password' => get_option('wbw_bmlt_password')
            );
            $url = get_option('wbw_bmlt_server_address') . "index.php";

            // error_log("AUTH URL = " . $url);
            $ret = $this->post($url, null, $postargs);

            if (is_wp_error($ret))
            {
                return new WP_Error('wbw', 'authenticateRootServer: Server Failure');
            }  

            if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
            {
                $this->cookies = null;
                return new WP_Error('wbw', 'authenticateRootServer: Authentication Failure');
            }
            
            $this->cookies = wp_remote_retrieve_cookies($ret);

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
        $ret = wp_safe_remote_get($url, $this->set_args($cookies));
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
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
        error_log("POSTING URL = " . $url);
        // error_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        // error_log("*********");
        $ret = wp_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
        if (preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/', wp_remote_retrieve_body($ret))) // best way I could find to check for invalid login
        {
            $ret =  $this->authenticateRootServer();
            if (is_wp_error($ret)) {
                return $ret;
            }
            // try once more in case it was a session timeout
            $ret = wp_safe_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
        }
        return $ret;
    }

    private function postsemantic($url, $cookies = null, $postargs)
    {
        ("POSTING SEMANTIC URL = " . $url);
        // error_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        // error_log("*********");
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
            error_log("our post body is " . $newargs);
            $ret = wp_safe_remote_post($url, $this->set_args($cookies, $newargs));
            error_log($this->vdump($ret));
            return $ret;
        }
    }

    private function postAuthenticatedRootServerRequest($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            return $ret;
        }
        return $this->post(get_option('wbw_bmlt_server_address') . $url, $this->cookies, $postargs);
    }

    private function postUnauthenticatedRootServerRequest($url, $postargs)
    {
        return $this->post(get_option('wbw_bmlt_server_address') . $url, null, $postargs);
    }

    private function postAuthenticatedRootServerRequestSemantic($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();
        if (is_wp_error($ret)) {
            return $ret;
        }
        return $this->postsemantic(get_option('wbw_bmlt_server_address') . $url, $this->cookies, $postargs);
    }

}

?>
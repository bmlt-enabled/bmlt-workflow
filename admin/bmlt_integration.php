
<?php

if (!defined('ABSPATH')) exit; // die if being called directly

class BMLTIntegration
{

    protected $cookies = null; // our authentication cookies
    protected $authfailure = 0; // authentication is failing - don't keep bashing the front door
    protected const MAXFAILS = 2; // how many failures we'll retry

    // postargs is an array
    public function postConfiguredRootServerRequest($url, $postargs)
    {
        if ($this->authfailure < self::MAXFAILS)
        {    
            return $this->postRootServerRequest(get_option('bmaw_bmlt_server_address') . $url, $postargs);
        }
        else
        {
            return new WP_Error( 'bmaw', "Fatal BMLT Authentication Failure - check the credentials in BMAW settings" );
        }
    }

    public function postConfiguredRootServerRequestSemantic($url, $postargs)
    {
        if ($this->authfailure < self::MAXFAILS)
        {    
            return $this->postRootServerRequestSemantic(get_option('bmaw_bmlt_server_address') . $url, $postargs);
        }
        else
        {
            return new WP_Error( 'bmaw', "Fatal BMLT Authentication Failure - check the credentials in BMAW settings" );
        }
    }
    public function getConfiguredRootServerRequest($url)
    {
        if ($this->authfailure < self::MAXFAILS)
        {
            return $this->getRootServerRequest(get_option('bmaw_bmlt_server_address') . $url);
        }
        else
        {
            return new WP_Error( 'bmaw', "Fatal BMLT Authentication Failure - check the credentials in BMAW settings" );
        }

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
                'c_comdef_admin_login' => get_option('bmaw_bmlt_username'),
                'c_comdef_admin_password' => get_option('bmaw_bmlt_password')
            );
            $url = get_option('bmaw_bmlt_server_address') . "index.php";

            error_log("AUTH URL = " . $url);
            $ret = $this->post($url, null, $postargs);
            if(preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/',$ret['body'])) // best way I could find to check for invalid login
            {
                $this->authfailure++;
                $this->cookies = null;
                return new WP_Error('bmaw','authenticateRootServer: Authentication Failure');
            }
            else
            {
                $this->authfailure = 0;
                $this->cookies = wp_remote_retrieve_cookies($ret);
            }
        }
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
        $ret = wp_remote_get($url, $this->set_args($cookies));
        if(preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/',$ret['body'])) // best way I could find to check for invalid login
        {
            $this->authfailure++;
            $this->cookies = null;
            $ret =  $this->authenticateRootServer();
            if( is_wp_error( $ret ) ) {
                return $ret;
            }    
            // try once more in case it was a session timeout
            $ret = wp_remote_post($url, $this->set_args($cookies));
        }
        return $ret;
    }

    private function post($url, $cookies = null, $postargs)
    {
        error_log("POSTING URL = " . $url);
        error_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        error_log("*********");
        $ret = wp_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
        if(preg_match('/.*\"c_comdef_not_auth_[1-3]\".*/',$ret['body'])) // best way I could find to check for invalid login
        {
            $this->authfailure++;
            $this->cookies = null;
            $ret =  $this->authenticateRootServer();
            if( is_wp_error( $ret ) ) {
                return $ret;
            }    
            // try once more in case it was a session timeout
            $ret = wp_remote_post($url, $this->set_args($cookies, http_build_query($postargs)));
        }
        return $ret;
    }

    private function postsemantic($url, $cookies = null, $postargs)
    {
        error_log("POSTING SEMANTIC URL = " . $url);
        error_log($this->vdump($this->set_args($cookies, http_build_query($postargs))));
        error_log("*********");
        $newargs = '';
        foreach ($postargs as $key => $value)
        {
            switch ($key)
            {
                case ('admin_action'):
                case ('meeting_id'):
                case ('flat');
                    $newargs .= $key.'='.$value;
                    break;
                default:
                    $newargs .= "meeting_field[]=".$key.','.$value;
            }
            $newargs .= '&';
        }
        if ($newargs != '')
        {   
            // chop trailing &
            $newargs = substr($newargs,0,-1);
            error_log("our post body is ".$newargs);
            $ret = wp_remote_post($url, $this->set_args($cookies, $newargs));
            error_log($this->vdump($ret));
            return $ret;
        }
    }

    private function getRootServerRequest($url)
    {
        error_log("GETROOTSERVERREQUEST COOKIES");
        error_log($this->vdump($this->cookies));
        error_log("*********");
        $ret =  $this->authenticateRootServer();
        if( is_wp_error( $ret ) ) {
            return $ret;
        }
        return $this->get($url, $this->cookies);
    }

    private function postRootServerRequest($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();
        if( is_wp_error( $ret ) ) {
            return $ret;
        }
        return $this->post($url, $this->cookies, $postargs);
    }

    private function postRootServerRequestSemantic($url, $postargs)
    {
        $ret =  $this->authenticateRootServer();
        if( is_wp_error( $ret ) ) {
            return $ret;
        }
        return $this->postsemantic($url, $this->cookies, $postargs);
    }

}

?>

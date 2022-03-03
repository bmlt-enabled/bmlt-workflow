
<?php

if (!defined('ABSPATH')) exit; // die if being called directly

class BMLTIntegration
{

    public function postConfiguredRootServerRequest($url, $postargs)
    {
        return $this->postRooServerRequest(get_option('bmaw_bmlt_server_address') . $url, $postargs);
    }

    public function getConfiguredRootServerRequest($url)
    {
        return $this->getRootServerRequest(get_option('bmaw_bmlt_server_address') . $url);
    }

    private function authenticateRootServer()
    {
        $query_string = http_build_query(array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => get_option('bmaw_bmlt_username'),
            'c_comdef_admin_password' => get_option('bmaw_bmlt_password'), '&'
        ));
        $url = get_option('bmaw_bmlt_server_address') . "server_admin/json.php?" . $query_string;
        return $this->get($url);
    }

    private function set_args($cookies)
    {
        $args = array(
            'timeout' => '120',
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36'
            ),
            'cookies' => isset($cookies) ? $cookies : null
        );
        return $args;
    }

    private function get($url, $cookies = null)
    {
        return wp_remote_get($url, $this->set_args($cookies));
    }

    private function post($url, $cookies = null, $postargs)
    {

        return wp_remote_post($url, $this->set_args($cookies), $postargs);
    }

    private function getRootServerRequest($url)
    {
        $cookies = null;
        $auth_response = $this->authenticateRootServer();
        $cookies = wp_remote_retrieve_cookies($auth_response);

        return $this->get($url, $cookies);
    }

    private function postRooServerRequest($url, $postargs)
    {
        $cookies = null;
        $auth_response = $this->authenticateRootServer();
        $cookies = wp_remote_retrieve_cookies($auth_response);

        return $this->post($url, $cookies, $postargs);
    }

}

?>

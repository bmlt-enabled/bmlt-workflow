<?php

namespace wbw\REST;

use wbw\BMLT\Integration;

class HandlerCore
{

    public function __construct($stub = null)
    {
        if (empty($stub))
        {
            $this->bmlt_integration = new Integration();
        }
        else
        {
            $this->bmlt_integration = $stub;
        }
    }

    // accepts raw string or array
    public function wbw_rest_success($message)
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

    public function wbw_rest_error($message, $code)
    {
        return new \WP_Error('wbw_error', $message, array('status' => $code));
    }

    public function wbw_rest_error_with_data($message, $code, array $data)
    {
        $data['status'] = $code;
        return new \WP_Error('wbw_error', $message, $data);
    }

  
}

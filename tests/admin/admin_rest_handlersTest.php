<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

define('ABSPATH', '99999999999');

// We require the file we need to test.
// require 'admin/admin_rest_handlers.php';
require 'admin/admin_rest_handlers.php';

function vdump($object)
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}

final class admin_rest_handlersTest extends TestCase
{

    protected function setVerboseErrorHandler() 
{
    $handler = function($errorNumber, $errorString, $errorFile, $errorLine) {
        echo "
ERROR INFO
Message: $errorString
File: $errorFile
Line: $errorLine
";
    };
    set_error_handler($handler);        
}

    protected function setUp(): void
    {
        $this->setVerboseErrorHandler();
        $basedir = dirname(dirname(dirname(__FILE__)));
        // echo $basedir;
        require($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        require_once($basedir . '/admin/bmlt_integration.php');

        Functions\when('wp_json_encode')->returnArg();
        if (!defined('CONST_OTHER_SERVICE_BODY')) {
            define('CONST_OTHER_SERVICE_BODY', '99999999999');
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', '99999999999');
        }

    }

    public function test_can_approve_close_meeting(): void
    {
        $json_post = array(
            "action_message" => ""
        );
        echo "here";
        $request   = new WP_REST_Request('POST', 'http://54.153.167.239/flop/wp-json/wbw/v1/submissions/14/approve');
        $request->set_header('content-type', 'application/json');
        $request->set_body($json_post);
        echo "here2";

        $rest = new wbw_submissions_rest_handlers();

        echo "here3";
        error_log(vdump($request));

        $response = $rest->approve_submission_handler($json_post);

        error_log(vdump($response));
    }
}

<?php

declare(strict_types=1);

use wbw\Debug;
use wbw\REST\Handlers\OptionsHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};
require_once('config_phpunit.php');

global $wbw_dbg;
$wbw_dbg = new Debug;

/**
 * @covers wbw\REST\Handlers\OptionsHandler
 * @uses wbw\Debug
 * @uses wbw\REST\HandlerCore
 */
final class OptionsHandlerTest extends TestCase
{
    protected function setVerboseErrorHandler()
    {
        $handler = function ($errorNumber, $errorString, $errorFile, $errorLine) {
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
        $basedir = getcwd();
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')){
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\apply_filters')->returnArg(2);
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('\absint')->returnArg();
        Functions\when('\get_option')->returnArg();
        Functions\when('\wbw_get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

// test for GET bmltserver (get server test settings)
    /**
     * @covers wbw\REST\Handlers\BMLTServerHandler::get_bmltserver_handler
     */
    public function test_can_get_bmltserver_with_success(): void
    {

        global $wbw_dbg;
        $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('GET');

        Functions\when('\wbw_get_option')->justReturn('success');
        $rest = new OptionsHandler();

        $response = $rest->post_wbw_backup_handler($request);

        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

    }

}
<?php

declare(strict_types=1);

use wbw\Debug;
use wbw\REST\Handlers\BMLTServerHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

if (!defined('WBW_DEBUG')) {
    define('WBW_DEBUG', true);
}
global $wbw_dbg;
$wbw_dbg = new Debug;

// get us through the header
if (!defined('ABSPATH')) {
    define('ABSPATH', '99999999999');
}

final class BMLTServerHandlerTest extends TestCase
{

// test for GET bmltserver (get server test settings)
    /**
     * @covers wbw\REST\Handlers::get_bmltserver_handler
     */
    public function test_can_get_bmltserver_with_success(): void
    {

        global $wbw_dbg;
        $wbw_dbg->debug_log("hi1");
        $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('GET');
        $wbw_dbg->debug_log("hi2");

        Functions\when('\get_option')->justReturn('success');
        $wbw_dbg->debug_log("hi3");
        $rest = new BMLTServerHandler();
        $wbw_dbg->debug_log("hi4");

        $response = $rest->get_bmltserver_handler($request);

        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $this->assertEquals($response->get_data()['wbw_bmlt_test_status'], 'success');
    }

    // test for GET bmltserver (get server test settings)
    /**
     * @covers wbw\REST\Handlers::get_bmltserver_handler
     */

    public function test_can_get_bmltserver_with_failure(): void
    {
        $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('GET');

        Functions\when('\get_option')->justReturn('failure');
        $rest = new BMLTServerHandler();

        $response = $rest->get_bmltserver_handler($request);

        global $wbw_dbg;
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $this->assertEquals($response->get_data()['wbw_bmlt_test_status'], 'failure');
    }

    // test for POST bmltserver
    /**
     * @covers wbw\REST\Handlers::post_bmltserver_handler
     */

    public function test_can_post_bmltserver_with_valid_parameters(): void
    {
        $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('wbw_bmlt_server_address', 'http://1.1.1.1/main_server/');
        $request->set_param('wbw_bmlt_username', 'test');
        $request->set_param('wbw_bmlt_password', 'test');
        Functions\when('\get_option')->justReturn('success');
        Functions\when('\update_option')->returnArg(1);
        // Functions\when('\wp_remote_retrieve_response_code')->justReturn('200');
        // Functions\when('\wp_remote_retrieve_body')->justReturn('<html></html');

        $stub = \Mockery::mock('Integration');
        /** @var Mockery::mock $stub test */
        $stub->shouldReceive('testServerAndAuth')->andReturn('true');

        Functions\when('\is_wp_error')->justReturn(false);

        $rest = new BMLTServerHandler($stub);

        $response = $rest->post_bmltserver_handler($request);

        global $wbw_dbg;
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $this->assertEquals($response->get_data()['wbw_bmlt_test_status'], 'success');
    }

    // test for POST bmltserver

    /**
     * @covers wbw\REST\Handlers::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_invalid_server(): void
    {
        $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('wbw_bmlt_server_address', 'test');
        $request->set_param('wbw_bmlt_username', 'test');
        $request->set_param('wbw_bmlt_password', 'test');
        Functions\when('\get_option')->justReturn('success');
        Functions\when('\update_option')->returnArg(1);
        $rest = new BMLTServerHandler();

        $response = $rest->post_bmltserver_handler($request);

        global $wbw_dbg;
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals($response->get_error_data()['wbw_bmlt_test_status'], 'failure');
    }

    /**
     * @covers wbw\REST\Handlers::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_blank_username(): void
    {
        $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('wbw_bmlt_server_address', 'test');
        $request->set_param('wbw_bmlt_username', '');
        $request->set_param('wbw_bmlt_password', 'test');
        Functions\when('\get_option')->justReturn('success');
        Functions\when('\update_option')->returnArg(1);
        $rest = new BMLTServerHandler();

        $response = $rest->post_bmltserver_handler($request);

        global $wbw_dbg;
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals($response->get_error_data()['wbw_bmlt_test_status'], 'failure');
    }

    /**
     * @covers wbw\REST\Handlers::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_blank_password(): void
    {
        $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('wbw_bmlt_server_address', 'test');
        $request->set_param('wbw_bmlt_username', 'test');
        $request->set_param('wbw_bmlt_password', '');
        Functions\when('\get_option')->justReturn('success');
        Functions\when('\update_option')->returnArg(1);
        $rest = new BMLTServerHandler();

        $response = $rest->post_bmltserver_handler($request);

        global $wbw_dbg;
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals($response->get_error_data()['wbw_bmlt_test_status'], 'failure');
    }
}
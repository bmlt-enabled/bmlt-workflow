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


declare(strict_types=1);

use bmltwf\REST\Handlers\BMLTServerHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};
require_once('config_phpunit.php');


/**
 * @covers bmltwf\REST\Handlers\BMLTServerHandler
 * @uses bmltwf\BMLTWF_Debug
 * @uses bmltwf\REST\HandlerCore
 * @uses bmltwf\BMLT\Integration
 */
final class BMLTServerHandlerTest extends TestCase
{
    use \bmltwf\BMLTWF_Debug;

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
        require_once($basedir . '/vendor/autoload.php');
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/wp/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\apply_filters')->returnArg(2);
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('\absint')->returnArg();
        Functions\when('\wp_remote_post')->returnArg();
        Functions\when('\get_option')->justReturn("failure");
        Functions\when('__')->returnArg();
        Functions\when('wp_is_json_media_type')->justReturn(true);

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();

    }

// test for GET bmltserver (get server test settings)
    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::get_bmltserver_handler
     */
    public function test_can_get_bmltserver_with_success(): void
    {
        
        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('GET');

        Functions\when('\get_option')->justReturn("success");

        $Intstub = \Mockery::mock('Integration');
        $Intstub->bmlt_root_server_version = '3.0.0';

        $rest = new BMLTServerHandler($Intstub);

        $response = $rest->get_bmltserver_handler($request);

        $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $this->assertEquals($response->get_data()['bmltwf_bmlt_test_status'], 'success');
    }

    // test for GET bmltserver (get server test settings)
    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::get_bmltserver_handler
     */
    public function test_can_get_bmltserver_with_failure(): void
    {

        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('GET');

        $Intstub = \Mockery::mock('Integration');
        $Intstub->bmlt_root_server_version = '3.0.0';
        $rest = new BMLTServerHandler($Intstub);

        $response = $rest->get_bmltserver_handler($request);

        
        $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $this->assertEquals($response->get_data()['bmltwf_bmlt_test_status'], 'failure');
    }

    // test for POST bmltserver
    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::post_bmltserver_handler
     */

    public function test_can_post_bmltserver_with_valid_parameters(): void
    {

        $request = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('bmltwf_bmlt_server_address', 'http://1.1.1.1/main_server/');
        $request->set_param('bmltwf_bmlt_username', 'test');
        $request->set_param('bmltwf_bmlt_password', 'test');

        Functions\when('\update_option')->returnArg(1);
        Functions\when('\wp_remote_retrieve_response_code')->justReturn('200');

        $stub = \Mockery::mock('Integration');
        $stub->bmlt_root_server_version = '3.0.0';
        /** @var Mockery::mock $stub test */
        $stub->shouldReceive('testServerAndAuth')->andReturn('true');
        $stub->shouldReceive('is_valid_bmlt_server')->andReturn('true');
        $stub->shouldReceive('is_supported_server')->andReturn('true');

        Functions\when('\is_wp_error')->justReturn(false);

        $rest = new BMLTServerHandler($stub);

        $response = $rest->post_bmltserver_handler($request);

        
        $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals($response->get_data()['bmltwf_bmlt_server_status'], 'true');
        $this->assertEquals($response->get_data()['bmltwf_bmlt_login_status'], 'true');

    }

    // test for POST bmltserver

    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_invalid_server(): void
    {
        $request = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('bmltwf_bmlt_server_address', 'test');
        $request->set_param('bmltwf_bmlt_username', 'test');
        $request->set_param('bmltwf_bmlt_password', 'test');

        Functions\when('\update_option')->returnArg(1);

        $Intstub = \Mockery::mock('Integration');
        $Intstub->bmlt_root_server_version = '3.0.0';

        $rest = new BMLTServerHandler($Intstub);

        $response = $rest->post_bmltserver_handler($request);

        $this->debug_log(($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals($response->get_error_data()['bmltwf_bmlt_server_status'], 'false');
    }

    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_blank_username(): void
    {
        $request = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('bmltwf_bmlt_server_address', 'test');
        $request->set_param('bmltwf_bmlt_username', '');
        $request->set_param('bmltwf_bmlt_password', 'test');

        Functions\when('\update_option')->returnArg(1);
        $Intstub = \Mockery::mock('Integration');
        $Intstub->bmlt_root_server_version = '3.0.0';

        $rest = new BMLTServerHandler($Intstub);

        $response = $rest->post_bmltserver_handler($request);

        $this->debug_log(($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals($response->get_error_data()['bmltwf_bmlt_login_status'], 'unknown');
    }

    /**
     * @covers bmltwf\REST\Handlers\BMLTServerHandler::post_bmltserver_handler
     */
    public function test_cant_post_bmltserver_with_blank_password(): void
    {
        $request = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/bmltserver");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/bmltserver");
        $request->set_method('POST');
        $request->set_param('bmltwf_bmlt_server_address', 'test');
        $request->set_param('bmltwf_bmlt_username', 'test');
        $request->set_param('bmltwf_bmlt_password', '');

        Functions\when('\update_option')->returnArg(1);
        $Intstub = \Mockery::mock('Integration');
        $Intstub->bmlt_root_server_version = '3.0.0';

        $rest = new BMLTServerHandler($Intstub);

        $response = $rest->post_bmltserver_handler($request);

        $this->debug_log(($response));

        $this->assertInstanceOf(WP_Error::class, $response);
        //        $data["bmltwf_bmlt_server_status"] = "true";

        $this->assertEquals($response->get_error_data()['bmltwf_bmlt_login_status'], 'unknown');
    }
}
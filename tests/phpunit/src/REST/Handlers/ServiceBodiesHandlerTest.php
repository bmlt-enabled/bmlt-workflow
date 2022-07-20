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

use wbw\REST\Handlers\ServiceBodiesHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');


/**
 * @covers wbw\REST\Handlers\ServiceBodiesHandler
 * @uses wbw\WBW_Debug
 * @uses wbw\REST\HandlerCore
 * @uses wbw\BMLT\Integration
 * @uses wbw\WBW_Database
 * @uses wbw\WBW_WP_Options
 */
final class ServiceBodiesHandlerTest extends TestCase
{
    use \wbw\WBW_Debug;

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
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\apply_filters')->returnArg(2);
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('\absint')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();

        unset($this->wbw_dbg);
    }

    /**
     * @covers wbw\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_simple_with_success(): void
    {

        
        $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/servicebodies");
        $request->set_method('GET');

        $sblookup = array(
            '0' => array(
                "service_body_bigint" => "2",
                "service_body_name" => "Sydney Metro",
                "show_on_form" => "1"
            ),
            '1' => array(
                "service_body_bigint" =>"3",
                "service_body_name" =>"Sydney North",
                "show_on_form" =>"1"
            ),
            '2' => array(
                "service_body_bigint" => "4",
                "service_body_name" =>"Sydney South",
                "show_on_form" =>"1"
            )
        );

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('prepare')->andReturn("SELECT * from anything");
        $wpdb->shouldReceive('get_results')->andReturn($sblookup);
        $wpdb->prefix = "";


        $rest = new ServiceBodiesHandler();

        $response = $rest->get_service_bodies_handler($request);

        $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->debug_log(($response));
        $this->assertEquals($response->get_data()['2']['name'], 'Sydney Metro');
    }


    // /**
    //  * @covers wbw\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
    //  */
    // public function test_can_get_service_bodies_detail_with_success(): void
    // {

        
    //     $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/servicebodies");
    //     $request->set_header('content-type', 'application/json');
    //     $request->set_route("/wbw/v1/servicebodies");
    //     $request->set_method('GET');
    //     $request->set_param('detail','true');

    //     Functions\when('\current_user_can')->justReturn(true);
    //     Functions\when('\wp_remote_retrieve_body')->justReturn('<html></html');

    //     $sblookup = array(
    //         '0' => array(
    //             "service_body_bigint" => "2",
    //             "service_body_name" => "Sydney Metro",
    //             "show_on_form" => "1"
    //         ),
    //         '1' => array(
    //             "service_body_bigint" =>"3",
    //             "service_body_name" =>"Sydney North",
    //             "show_on_form" =>"1"
    //         ),
    //         '2' => array(
    //             "service_body_bigint" => "4",
    //             "service_body_name" =>"Sydney South",
    //             "show_on_form" =>"0"
    //         )
    //     );
    //     global $wpdb;
    //     $wpdb =  Mockery::mock('wpdb');
    //     /** @var Mockery::mock $wpdb test */
    //     $wpdb->shouldReceive('get_results')->andReturn($sblookup)
    //     ->shouldReceive('get_col')->andreturn(array("1","2"))
    //     ->shouldReceive('prepare')->andreturn(array("1","2"))
    //     ->shouldReceive('query')->andreturn(array("1","2"))

    //     $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
    //     /** @var Mockery::mock $WBW_WP_Options test */
    //     Functions\when('\get_option')->justReturn("success");
    //     $sblist = array('body'=>'[{"id":"1","parent_id":"0","name":"toplevel","description":"","type":"AS"},{"id":"2","parent_id":"1","name":"a-level1","description":"","type":"AS"},{"id":"3","parent_id":"1","name":"b-level1","description":"","type":"AS"},{"id":"4","parent_id":"1","name":"c-level","description":"test c level","type":"AS"},{"id":"5","parent_id":"4","name":"d-level","description":"d-level test","type":"AS"}]');
    //     $Intstub = \Mockery::mock('Integration');
    //     /** @var Mockery::mock $Intstub test */
    //     $Intstub->shouldReceive('postUnauthenticatedRootServerRequest')->andReturn($sblist);

    //     $rest = new ServiceBodiesHandler($Intstub, $WBW_WP_Options);

    //     $response = $rest->get_service_bodies_handler($request);

    //     $this->debug_log(($response));

    //     $this->assertInstanceOf(WP_REST_Response::class, $response);
    //     $this->debug_log(($response));
    //     $this->assertEquals($response->get_data()['2']['name'], 'Sydney Metro');
    // }

}

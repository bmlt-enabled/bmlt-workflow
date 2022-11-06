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

use bmltwf\REST\Handlers\ServiceBodiesHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');


/**
 * @covers bmltwf\REST\Handlers\ServiceBodiesHandler
 * @uses bmltwf\BMLTWF_Debug
 * @uses bmltwf\REST\HandlerCore
 * @uses bmltwf\BMLT\Integration
 * @uses bmltwf\BMLTWF_Database
 * @uses bmltwf\BMLTWF_WP_Options
 */
final class ServiceBodiesHandlerTest extends TestCase
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
        Functions\when('wp_remote_post')->returnArg();

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();

        unset($this->bmltwf_dbg);
    }

    /**
     * @covers bmltwf\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_simple_with_success(): void
    {

        
        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/servicebodies");
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


    /**
     * @covers bmltwf\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_detail_with_success(): void
    {

        
        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/servicebodies");
        $request->set_method('GET');
        $request->set_param('detail','true');

        Functions\when('\current_user_can')->justReturn(true);

        $sblookup = array();
        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('get_results')->andReturn($sblookup)
        ->shouldReceive('get_col')->andreturn(array("1","2"))
        ->shouldReceive('prepare')->andreturn(array("1","2"))
        ->shouldReceive('query')->andreturn(array("1","2"));

        $BMLTWF_WP_Options =  Mockery::mock('BMLTWF_WP_Options');
        /** @var Mockery::mock $BMLTWF_WP_Options test */
        Functions\when('\get_option')->justReturn("success");

        Functions\expect('wp_remote_retrieve_body')->twice()->andReturn(
            '{"service_body":[{"id":1,"name":"toplevel","permissions":2},{"id":2,"name":"a-level1","permissions":3},{"id":3,"name":"b-level1","permissions":2}]}',
            '[{"id":"1","parent_id":"0","name":"toplevel","description":"","type":"AS"},{"id":"2","parent_id":"1","name":"a-level1","description":"this is the description for a-level1","type":"AS"},{"id":"3","parent_id":"1","name":"b-level1","description":"this is the description for b-level1","type":"AS"},{"id":"4","parent_id":"0","name":"test-no-permissions","description":"","type":"WS"}]'
        );

        $Intstub = \Mockery::mock('Integration');
        /** @var Mockery::mock $Intstub test */
        $bodies = array('body'=>'');
        $Intstub->shouldReceive('postAuthenticatedRootServerRequest')->andReturn($bodies);

        $sblist = array('body'=>'{"service_body":[{"id":1,"name":"toplevel","type":"AS"},"service_body_type":"Area Service Committee","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}},"service_bodies":{"service_body":[{"id":2,"name":"a-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","contact_email":"a-level1@a.com","editors":{"service_body_editors":{"editor":[{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"},{"id":3,"admin_type":"direct","admin_name":"sba"}]}},{"id":3,"name":"b-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"direct","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}}}]}},{"id":4,"name":"test-no-permissions","type":"WS"},"service_body_type":"World Service Conference","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"}}}]}');
        // $Intstub->shouldReceive('postUnauthenticatedRootServerRequest')->andReturn($sblist);

        $rest = new ServiceBodiesHandler($Intstub, $BMLTWF_WP_Options);

        $response = $rest->get_service_bodies_handler($request);

        // $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        // $this->debug_log(($response));
        $this->assertEquals($response->get_data()['2']['name'], 'a-level1');
    }

    /**
     * @covers bmltwf\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_detail_with_non_editable_service_body(): void
    {
        
        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/servicebodies");
        $request->set_method('GET');
        $request->set_param('detail','true');

        Functions\when('\current_user_can')->justReturn(true);

        $sblookup = array();
        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('get_results')->andReturn($sblookup)
        ->shouldReceive('get_col')->andreturn(array("1","2"))
        ->shouldReceive('prepare')->andreturn(array("1","2"))
        ->shouldReceive('query')->andreturn(array("1","2"));

        $BMLTWF_WP_Options =  Mockery::mock('BMLTWF_WP_Options');
        /** @var Mockery::mock $BMLTWF_WP_Options test */
        Functions\when('\get_option')->justReturn("success");

        Functions\expect('wp_remote_retrieve_body')->twice()->andReturn(
            '{"service_body":[{"id":1,"name":"toplevel","permissions":2},{"id":2,"name":"a-level1","permissions":3},{"id":3,"name":"b-level1","permissions":2}]}',
            '[{"id":"1","parent_id":"0","name":"toplevel","description":"","type":"AS"},{"id":"2","parent_id":"1","name":"a-level1","description":"this is the description for a-level1","type":"AS"},{"id":"3","parent_id":"1","name":"b-level1","description":"this is the description for b-level1","type":"AS"},{"id":"4","parent_id":"0","name":"test-no-permissions","description":"","type":"WS"}]'
        );

        $Intstub = \Mockery::mock('Integration');
        /** @var Mockery::mock $Intstub test */
        $bodies = array('body'=>'');
        $Intstub->shouldReceive('postAuthenticatedRootServerRequest')->andReturn($bodies);

        $sblist = array('body'=>'{"service_body":[{"id":1,"name":"toplevel","type":"AS"},"service_body_type":"Area Service Committee","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}},"service_bodies":{"service_body":[{"id":2,"name":"a-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","contact_email":"a-level1@a.com","editors":{"service_body_editors":{"editor":[{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"},{"id":3,"admin_type":"direct","admin_name":"sba"}]}},{"id":3,"name":"b-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"direct","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}}}]}},{"id":4,"name":"test-no-permissions","type":"WS"},"service_body_type":"World Service Conference","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"}}}]}');
        // $Intstub->shouldReceive('postUnauthenticatedRootServerRequest')->andReturn($sblist);

        $rest = new ServiceBodiesHandler($Intstub, $BMLTWF_WP_Options);

        $response = $rest->get_service_bodies_handler($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        // test-no-permissions should not show as we dont even see it in the permissions list
        $this->assertArrayNotHasKey('4',$response->get_data());
    }

        /**
     * @covers bmltwf\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_detail_more_service_bodies_added(): void
    {
        
        $request = new WP_REST_Request('GET', "http://3.25.141.92/flop/wp-json/bmltwf/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/bmltwf/v1/servicebodies");
        $request->set_method('GET');
        $request->set_param('detail','true');

        Functions\when('\current_user_can')->justReturn(true);

        $sblookup = array();
        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('get_results')->andReturn($sblookup)
        // say that we only have service body 1 in the db
        ->shouldReceive('get_col')->andreturn(array("1"))
        ->shouldReceive('prepare')->andreturn(array("1","2")) 
        // we'll see query 2 times if we have to add sb 2 and 3 into to the db, then 3 more times for the description/name updates
        ->shouldReceive('query')->times(5)->andreturn(array("1","2"));

        $BMLTWF_WP_Options =  Mockery::mock('BMLTWF_WP_Options');
        /** @var Mockery::mock $BMLTWF_WP_Options test */
        Functions\when('\get_option')->justReturn("success");

        Functions\expect('wp_remote_retrieve_body')->twice()->andReturn(
            '{"service_body":[{"id":1,"name":"toplevel","permissions":2},{"id":2,"name":"a-level1","permissions":3},{"id":3,"name":"b-level1","permissions":2}]}',
            '[{"id":"1","parent_id":"0","name":"toplevel","description":"","type":"AS"},{"id":"2","parent_id":"1","name":"a-level1","description":"this is the description for a-level1","type":"AS"},{"id":"3","parent_id":"1","name":"b-level1","description":"this is the description for b-level1","type":"AS"},{"id":"4","parent_id":"0","name":"test-no-permissions","description":"","type":"WS"}]'
        );

        $Intstub = \Mockery::mock('Integration');
        /** @var Mockery::mock $Intstub test */
        $bodies = array('body'=>'');
        $Intstub->shouldReceive('postAuthenticatedRootServerRequest')->andReturn($bodies);

        $sblist = array('body'=>'{"service_body":[{"id":1,"name":"toplevel","type":"AS"},"service_body_type":"Area Service Committee","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}},"service_bodies":{"service_body":[{"id":2,"name":"a-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","contact_email":"a-level1@a.com","editors":{"service_body_editors":{"editor":[{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"},{"id":3,"admin_type":"direct","admin_name":"sba"}]}},{"id":3,"name":"b-level1","type":"AS"},"service_body_type":"Area Service Committee","parent_service_body":"toplevel","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"direct","admin_name":"sba"},"meeting_list_editors":{"editor":{"id":2,"admin_type":"direct","admin_name":"bmlt-workflow-bot"}}}}]}},{"id":4,"name":"test-no-permissions","type":"WS"},"service_body_type":"World Service Conference","editors":{"service_body_editors":{"editor":{"id":3,"admin_type":"principal","admin_name":"sba"}}}]}');
        // $Intstub->shouldReceive('postUnauthenticatedRootServerRequest')->andReturn($sblist);

        $rest = new ServiceBodiesHandler($Intstub, $BMLTWF_WP_Options);

        $response = $rest->get_service_bodies_handler($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);

    }
}

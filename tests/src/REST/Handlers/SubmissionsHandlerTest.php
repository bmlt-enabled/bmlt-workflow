<?php


declare(strict_types=1);

// debug settings
use wbw\Debug;
use wbw\REST\Handlers\SubmissionsHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};
require_once('config_phpunit.php');

global $wbw_dbg;
$wbw_dbg = new Debug;

class SubmissionsHandlerTest_my_wp_user
{
    public function __construct($id, $name)
    {
        $this->ID = $id;
        $this->user_login = $name;
        $this->user_email = "a@a.com";
    }

    public function get()
    {
        return $this->ID;
    }
}
/**
 * @covers wbw\REST\Handlers\SubmissionsHandler
 * @uses wbw\Debug
 * @uses wbw\REST\HandlerCore
 * @uses wbw\BMLT\Integration
 */
final class SubmissionsHandlerTest extends TestCase
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
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        if (!class_exists('wpdb')){
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('get_option')->returnArg();
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('wp_json_encode')->returnArg();
        Functions\when('get_site_url')->justReturn('http://127.0.0.1/wordpress');
        Functions\when('wp_remote_post')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('{"0":{"id":"1","key_string":"0","name_string":"0"},"1":{"id":"2","key_string":"0","name_string":"0"},"2":{"id":"3","key_string":"0","name_string":"0"}}');
        Functions\when('is_wp_error')->justReturn(false);

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    private function generate_approve_request($test_submission_id, $body)
    {
        $json_post = json_encode($body);

        $request   = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/submissions/{$test_submission_id}/approve");
        $request->set_header('content-type', 'application/json');
        $request->set_body($json_post);
        $request->set_url_params(array('id' => $test_submission_id));
        $request->set_route("/wbw/v1/submissions/{$test_submission_id}/approve");
        $request->set_method('POST');

        return $request;
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_close(): void
    {
        global $wbw_dbg;

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_close",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "meeting_id" => "3277",
            "submit" => "Submit Form",
            "additional_info" => "I'd like to close the meeting please",
            "group_relationship" => "Group Member",
            "add_email" => "yes",
        );

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);

        $handlers = new SubmissionsHandler();
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $wbw_dbg->debug_log("TEST RESPONSE");
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        // $wbw_dbg->debug_log($email_addresses);
        // $this->assertEquals($email_addresses,'a@a.com,a@a.com');
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_request_other(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_other",
            "other_reason" => "testing other",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_meeting_name(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_meeting_format(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "format_shared_id_list" => "1",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_if_meeting_format_has_leading_or_trailing_commas(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "format_shared_id_list" => ",,1,2,,,,",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_no_starter_kit_requested(): void
    {
        global $wbw_dbg;

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "10:00:00",
            "duration_time" => "01:00:00",
            "location_text" => "test location",
            "location_street" => "test street",
            "location_municipality" => "test municipality",
            "location_province" => "test province",
            "location_postal_code_1" => "12345",
            "weekday_tinyint" => "1",
            "service_body_bigint" => "99",
            "format_shared_id_list" => "1",
            "starter_kit_required" => "no",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_starter_kit_requested(): void
    {
        global $wbw_dbg;

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "10:00:00",
            "duration_time" => "01:00:00",
            "location_text" => "test location",
            "location_street" => "test street",
            "location_municipality" => "test municipality",
            "location_province" => "test province",
            "location_postal_code_1" => "12345",
            "weekday_tinyint" => "1",
            "service_body_bigint" => "99",
            "format_shared_id_list" => "1",
            "starter_kit_required" => "yes",
            "starter_kit_postal_address" => "my house",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2,"test test"));
        Functions\when('wp_mail')->justReturn('true');

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    //
    // FAILURE TESTING
    //

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_if_starter_kit_answer_missing(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "10:00:00",
            "duration_time" => "01:00:00",
            "location_text" => "test location",
            "location_street" => "test street",
            "location_municipality" => "test municipality",
            "location_province" => "test province",
            "location_postal_code_1" => "12345",
            "weekday_tinyint" => "1",
            "service_body_bigint" => "99",
            "format_shared_id_list" => "1",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */

        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        Functions\expect('wp_mail')->never();

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_format_list_has_garbage(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "format_shared_id_list" => "aeeaetalkj2,7,8,33,54,55",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        Functions\expect('wp_mail')->never();

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_too_big(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "weekday_tinyint" => "9999",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        Functions\expect('wp_mail')->never();

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_zero(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "weekday_tinyint" => "0",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        Functions\expect('wp_mail')->never();

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_garbage(): void
    {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "weekday_tinyint" => "aerear9",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "group_relationship" => "Group Member",
            "add_email" => "yes",

        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        Functions\expect('wp_mail')->never();

        $handlers = new SubmissionsHandler;
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

        /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */
    public function test_can_approve_change_meeting(): void
    {
        $test_submission_id = '14';

        $body = '';

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_name":"Ashfield change name","weekday_tinyint":"5","format_shared_id_list":"1,4,8,14,54,55","group_relationship":"Group Member","additional_info":"pls approve","original_meeting_name":"Ashfield"}',
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp]);

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new SubmissionsHandler($bmlt);
// global $wbw_dbg;
// $wbw_dbg->debug_log('APPROVEREQUEST');

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_can_approve_close_meeting_with_unpublish(): void
    {
        $test_submission_id = '14';

        // no delete in body for unpublish
        $body = '';

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_email":"no","additional_info":"please close this meeting","meeting_name":"Ashfield Exodus NA"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new SubmissionsHandler($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        $this->assertArrayHasKey("set_meeting_change", $bmlt_input);
        $this->assertArrayNotHasKey("delete_meeting", $bmlt_input);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_can_approve_close_meeting_with_delete(): void
    {
        $test_submission_id = '14';

        $body = array("delete" => "true");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_email":"no","additional_info":"please close this meeting","meeting_name":"Ashfield Exodus NA"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new SubmissionsHandler($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        $this->assertArrayHasKey("delete_meeting", $bmlt_input);
        $this->assertArrayNotHasKey("set_meeting_change", $bmlt_input);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    //
    // EMAIL TESTING
    //

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_approve_change_meeting_sends_email_to_submitter(): void
    {
        $test_submission_id = '14';

        $body = '';

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_name":"Ashfield change name","weekday_tinyint":"5","format_shared_id_list":"1,4,8,14,54,55","group_relationship":"Group Member","add_email":"yes","additional_info":"pls approve","original_meeting_name":"Ashfield"}',
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);

        $rest = new SubmissionsHandler($bmlt);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_approve_change_meeting_sends_email_to_submitter_with_action_message(): void
    {
        $test_submission_id = '14';

        $body = array("action_message" => "hello there");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_name":"Ashfield change name","weekday_tinyint":"5","format_shared_id_list":"1,4,8,14,54,55","group_relationship":"Group Member","add_email":"yes","additional_info":"pls approve","original_meeting_name":"Ashfield"}',
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);

        $rest = new SubmissionsHandler($bmlt);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_approve_close_meeting_sends_email_to_submitter_with_delete(): void
    {
        $test_submission_id = '14';

        $body = array("delete" => "true", "action_message" => "your meeting is now deleted");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_email":"no","additional_info":"please close this meeting","meeting_name":"Ashfield Exodus NA"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new SubmissionsHandler($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
        $this->assertEquals($bmlt_input, array("bmlt_ajax_callback" => 1, "delete_meeting" => 3563));
        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */

    public function test_approve_close_meeting_sends_email_to_submitter_with_unpublish(): void
    {
        $test_submission_id = '14';

        $body = array("action_message" => "your meeting is now unpublished");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => 3563,
            'service_body_bigint' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_email":"no","additional_info":"please close this meeting","meeting_name":"Ashfield Exodus NA"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input));

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new SubmissionsHandler($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        $this->assertArrayHasKey("set_meeting_change", $bmlt_input);
        $this->assertArrayNotHasKey("delete_meeting", $bmlt_input);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

}

<?php


declare(strict_types=1);

use wbw\Debug;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

// We require the file we need to test.
require 'admin/meeting_update_form_handler.php';

class meeting_update_form_handlerTest_my_wp_user
{
    public function __construct()
    {
        $this->user_email = "a@a.com";
    }
}
final class meeting_update_form_handlerTest extends TestCase
{


    protected function setUp(): void
    {
        $basedir = dirname(dirname(dirname(__FILE__)));
        // echo $basedir;
        require($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('get_option')->returnArg();
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('wp_json_encode')->returnArg();
        Functions\when('get_site_url')->justReturn('http://127.0.0.1/wordpress');
        Functions\when('wp_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('{"0":{"id":"1","key_string":"0","name_string":"0"},"1":{"id":"2","key_string":"0","name_string":"0"},"2":{"id":"3","key_string":"0","name_string":"0"}}');
        Functions\when('is_wp_error')->justReturn(false);

        if (!defined('CONST_OTHER_SERVICE_BODY')) {
            define('CONST_OTHER_SERVICE_BODY', '99999999999');
        }
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    public function test_can_close(): void
    {

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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);

        $response = meeting_update_form_handler_rest($form_post);
        Debug\debug_log("TEST RESPONSE");
        Debug\debug_log(vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        // Debug\debug_log($email_addresses);
        // $this->assertEquals($email_addresses,'a@a.com,a@a.com');
    }

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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }


    public function test_can_create_new_with_no_starter_kit_requested(): void
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        Debug\debug_log(vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_can_create_new_with_starter_kit_requested(): void
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);
        Functions\when('wp_mail')->justReturn('true');

        $response = meeting_update_form_handler_rest($form_post);
        Debug\debug_log(vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    //
    // EMAIL TESTING
    //

    // public function test_email_send_for_starter_kit_requested(): void
    // {

    //     $form_post = array(
    //         "action" => "meeting_update_form_response",
    //         "update_reason" => "reason_new",
    //         "meeting_name" => "testing name change",
    //         "meeting_id" => "3277",
    //         "start_time" => "10:00:00",
    //         "duration_time" => "01:00:00",
    //         "location_text" => "test location",
    //         "location_street" => "test street",
    //         "location_municipality" => "test municipality",
    //         "location_province" => "test province",
    //         "location_postal_code_1" => "12345",
    //         "weekday_tinyint" => "1",
    //         "service_body_bigint" => "99",
    //         "format_shared_id_list" => "1",
    //         "starter_kit_required" => "yes",
    //         "starter_kit_postal_address" => "my house",
    //         "first_name" => "joe",
    //         "last_name" => "joe",
    //         "email_address" => "joe@joe.com",
    //         "submit" => "Submit Form",
    //         "group_relationship" => "Group Member",
    //         "add_email" => "yes",

    //     );

    //     global $wpdb;
    //     $wpdb = Mockery::mock('wpdb');
    //     /** @var Mockery::mock $wpdb test */
    //     // handle db insert of submission
    //     $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
    //     // handle email to service body
    //     $wpdb->shouldReceive('prepare')->andReturn(true);
    //     $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
    //     Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);

    //     // we should generate 3 mails - one to the trusted servants  one to the form submitter and one to the fso

    //     // service body users
    //     Functions\expect('wp_mail')->times(1)->with("a@a.com,a@a.com", Mockery::any(), Mockery::any(), Mockery::any());
    //     // submitter
    //     Functions\expect('wp_mail')->times(1)->with("joe@joe.com", Mockery::any(), Mockery::any(), Mockery::any());
    //     // fso
    //     Functions\expect('wp_mail')->times(1)->with("wbw_fso_email_address", Mockery::any(), Mockery::any(), Mockery::any());

    //     $response = meeting_update_form_handler_rest($form_post);
    //     Debug\debug_log(vdump($response));
    //     $this->assertInstanceOf(WP_REST_Response::class, $response);
    //     $this->assertEquals(200, $response->get_status());
    // }

    // public function test_email_send_for_no_starter_kit_requested(): void
    // {

    //     $form_post = array(
    //         "action" => "meeting_update_form_response",
    //         "update_reason" => "reason_new",
    //         "meeting_name" => "testing name change",
    //         "meeting_id" => "3277",
    //         "start_time" => "10:00:00",
    //         "duration_time" => "01:00:00",
    //         "location_text" => "test location",
    //         "location_street" => "test street",
    //         "location_municipality" => "test municipality",
    //         "location_province" => "test province",
    //         "location_postal_code_1" => "12345",
    //         "weekday_tinyint" => "1",
    //         "service_body_bigint" => "99",
    //         "format_shared_id_list" => "1",
    //         "starter_kit_required" => "no",
    //         "first_name" => "joe",
    //         "last_name" => "joe",
    //         "email_address" => "joe@joe.com",
    //         "submit" => "Submit Form",
    //         "group_relationship" => "Group Member",
    //         "add_email" => "yes",

    //     );

    //     global $wpdb;
    //     $wpdb = Mockery::mock('wpdb');
    //     /** @var Mockery::mock $wpdb test */
    //     // handle db insert of submission
    //     $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
    //     // handle email to service body
    //     $wpdb->shouldReceive('prepare')->andReturn(true);
    //     $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
    //     Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new meeting_update_form_handlerTest_my_wp_user);

    //     // we should generate 3 mails - one to the trusted servants  one to the form submitter and one to the fso

    //     // service body users
    //     Functions\expect('wp_mail')->times(1)->with("a@a.com,a@a.com", Mockery::any(), Mockery::any(), Mockery::any());
    //     // submitter
    //     Functions\expect('wp_mail')->times(1)->with("joe@joe.com", Mockery::any(), Mockery::any(), Mockery::any());

    //     $response = meeting_update_form_handler_rest($form_post);
    //     Debug\debug_log(vdump($response));
    //     $this->assertInstanceOf(WP_REST_Response::class, $response);
    //     $this->assertEquals(200, $response->get_status());
    // }



    //
    // FAILURE TESTING
    //

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

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

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

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

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

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

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

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

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

        $response = meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

}

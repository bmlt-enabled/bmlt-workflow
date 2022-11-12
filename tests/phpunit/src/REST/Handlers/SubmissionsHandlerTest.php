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


use bmltwf\REST\Handlers\SubmissionsHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');


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
 * @covers bmltwf\REST\Handlers\SubmissionsHandler
 * @uses bmltwf\BMLTWF_Debug
 * @uses bmltwf\REST\HandlerCore
 * @uses bmltwf\BMLT\Integration
 * @uses bmltwf\BMLTWF_Database
 */
final class SubmissionsHandlerTest extends TestCase
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
print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,5));
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
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('wp_json_encode')->justReturn('{"contact_number_confidential":"12345","group_relationship":"Group Member","add_email":"yes","service_body_bigint":2,"additional_info":"my additional info","meeting_name":"virtualmeeting randwick","weekday_tinyint":"2","start_time":"20:30:00"}');
        Functions\when('get_site_url')->justReturn('http://127.0.0.1/wordpress');

        $this->meeting = ' { "id_bigint": "3563", "worldid_mixed": "", "shared_group_id_bigint": "", "service_body_bigint": "3", "weekday_tinyint": "2", "venue_type": "1", "start_time": "19:00:00", "duration_time": "01:15:00", "time_zone": "", "formats": "BT", "lang_enum": "en", "longitude": "0", "latitude": "0", "distance_in_km": "", "distance_in_miles": "", "email_contact": "", "meeting_name": "Test Monday Night Meeting", "location_text": "Glebe Town Hall", "location_info": "", "location_street": "160 Johns Road", "location_city_subsection": "", "location_neighborhood": "", "location_municipality": "Glebe", "location_sub_province": "", "location_province": "NSW", "location_postal_code_1": "NSW", "location_nation": "", "comments": "", "train_lines": "", "bus_lines": "", "contact_phone_2": "", "contact_email_2": "", "contact_name_2": "", "contact_phone_1": "", "contact_email_1": "", "contact_name_1": "", "zone": "", "phone_meeting_number": "", "virtual_meeting_link": "", "virtual_meeting_additional_info": "", "published": "1", "root_server_uri": "http:", "format_shared_id_list": "3" } ';

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();

        unset($this->bmltwf_dbg);
    }

    private function generate_approve_request($test_submission_id, $body)
    {
        $json_post = json_encode($body);

        $request   = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/submissions/{$test_submission_id}/approve");
        $request->set_header('content-type', 'application/json');
        $request->set_body($json_post);
        $request->set_url_params(array('id' => $test_submission_id));
        $request->set_route("/bmltwf/v1/submissions/{$test_submission_id}/approve");
        $request->set_method('POST');

        return $request;
    }

    private function stub_bmltv2($json_meeting, &$bmlt_input)
    {
        $resp = $json_meeting;
        $formats = '[ { "@attributes": { "sequence_index": "0" }, "key_string": "B", "name_string": "Beginners", "description_string": "This meeting is focused on the needs of new members of NA.", "lang": "en", "id": "1", "world_id": "BEG" }, { "@attributes": { "sequence_index": "1" }, "key_string": "BL", "name_string": "Bi-Lingual", "description_string": "This Meeting can be attended by speakers of English and another language.", "lang": "en", "id": "2", "world_id": "LANG" }, { "@attributes": { "sequence_index": "2" }, "key_string": "BT", "name_string": "Basic Text", "description_string": "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.", "lang": "en", "id": "3", "world_id": "BT" }]';
        // $bmlt = Mockery::mock('overload:bmltwf\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input))
            ->shouldReceive('geolocateAddress')->andreturn(array("latitude" => 1, "longitude" => 1))
            ->shouldReceive('retrieve_single_meeting')->andreturn(json_decode($resp, true))
            ->shouldReceive('getMeetingFormatsv2')->andreturn(json_decode($formats, true))
            ->shouldReceive('isAutoGeocodingEnabled')->andreturn(true)
            ->shouldReceive('is_v3_server')->andreturn(false);

        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));
        return $bmlt;
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_close(): void
    {
        

        $form_post = array(
            "update_reason" => "reason_close",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "meeting_id" => "3277",
            "service_body_bigint" => "1",
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
        $wpdb->prefix = "";

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');


        $retrieve_single_response = $this->meeting;
        $this->debug_log("THISMEETING");
        $this->debug_log(($retrieve_single_response));

        $bmlt_input = '';
        Functions\when('\get_option')->justReturn("success");
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log("TEST RESPONSE");
        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        // $this->debug_log($email_addresses);
        // $this->assertEquals($email_addresses,'a@a.com,a@a.com');
    }


    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_meeting_name(): void
    {

        $form_post = array(
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "service_body_bigint" => "6",
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
        $wpdb->prefix = "";

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $resp = '{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"6","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}';
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($resp, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        
        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_service_body(): void
    {

        $form_post = array(
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "service_body_bigint" => "1", // changing from 6 to 1
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
        Functions\when('wp_mail')->justReturn('true');

        $resp = '{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"6","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}';

        $bmlt_input = '';

        Functions\when('\get_option')->justReturn("success");
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($resp, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(\WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_meeting_format(): void
    {

        $form_post = array(
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "service_body_bigint" => "3",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "format_shared_id_list" => "1",
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_if_meeting_format_has_leading_or_trailing_commas(): void
    {

        $form_post = array(
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "first_name" => "joe",
            "last_name" => "joe",
            "service_body_bigint" => "3",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form",
            "format_shared_id_list" => ",,1,2,,,,",
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        
        $this->debug_log(($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }


    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_no_starter_kit_requested(): void
    {
        
        $form_post = array(
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_alpha_postcode(): void
    {
        
        $form_post = array(
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "10:00:00",
            "duration_time" => "01:00:00",
            "location_text" => "test location",
            "location_street" => "test street",
            "location_municipality" => "test municipality",
            "location_province" => "test province",
            "location_postal_code_1" => "P85 FG02",
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_with_bad_start_time(): void
    {
        
        $form_post = array(
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "12345",
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

        Functions\when('\get_option')->justReturn("success");
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

        /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_with_bad_duration_time(): void
    {
        
        $form_post = array(
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "3277",
            "start_time" => "10:00:00",
            "duration_time" => "9999",
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

        Functions\when('\get_option')->justReturn("success");

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_starter_kit_requested(): void
    {
        
        $form_post = array(
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
        $wpdb->prefix = "";

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    //
    // FAILURE TESTING
    //

    // /**
    //  * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
    //  */
    // public function test_cant_create_new_if_starter_kit_answer_missing(): void
    // {

    //     $form_post = array(
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
    //     $wpdb->shouldNotReceive('insert');
    //     $wpdb->prefix = "";

    //     Functions\expect('wp_mail')->never();

    //     $retrieve_single_response = $this->meeting;

    //     Functions\when('\get_option')->justReturn("success");

    //     $bmlt_input = '';
    //     $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
    //     $response = $handlers->meeting_update_form_handler_rest($form_post);

    //     $this->assertInstanceOf(WP_Error::class, $response);
    // }


    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_format_list_has_garbage(): void
    {

        $form_post = array(
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

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        $wpdb->prefix = "";

        Functions\expect('wp_mail')->never();
        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_too_big(): void
    {

        $form_post = array(
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

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        $wpdb->prefix = "";

        Functions\expect('wp_mail')->never();
        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_zero(): void
    {

        $form_post = array(
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

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        $wpdb->prefix = "";

        Functions\expect('wp_mail')->never();
        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_garbage(): void
    {

        $form_post = array(
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

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldNotReceive('insert');
        $wpdb->prefix = "";

        Functions\expect('wp_mail')->never();
        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;
        
        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv2($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;

        Functions\when('\get_option')->justReturn("success");

        $post_change_response = '[{"id_bigint":"3563"}]';


        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

        $bmlt_input = '';

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // 
        // $this->debug_log('APPROVEREQUEST');

        // $this->debug_log(($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->debug_log(($response));
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);

    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;
        $bmlt_input = '';

        Functions\when('\get_option')->justReturn("success");

        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

        $post_change_response = '[{"published":"0", "success":"true"}]';

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
        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // $this->debug_log(($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);

    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        $retrieve_single_response = $this->meeting;
        $post_change_response = '[{"id_bigint":"3563"}]';
        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");

        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);

        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('deleteMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);


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

        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // $this->debug_log(($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);

    }

    //
    // EMAIL TESTING
    //

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        $retrieve_single_response = $this->meeting;

        $post_change_response = '[{"id_bigint":"3563"}]';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

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
        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);
    }


    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);


        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

        $post_change_response = '[{"id_bigint":"3563"}]';

        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );
        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);


        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());


        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");



        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make deletemeeting just return true
        $stub_bmltv2->shouldReceive('deleteMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

        $post_change_response = '[{"id_bigint":"3563"}]';

        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );
        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');


        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
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

        $retrieve_single_response = $this->meeting;

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $bmlt_input = '';
        $stub_bmltv2 = $this->stub_bmltv2($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv2->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv2);

        $post_change_response = '[{"report":"3563", "success":"true"}]';

        $wpdb->shouldReceive(
            [
                'prepare' => 'nothing',
                'get_row' => $row,
                'get_results' => 'nothing'
            ]
        );
        $wpdb->prefix = "";

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');


        // $this->debug_log(($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);


        // $this->debug_log(($response));
    }
}

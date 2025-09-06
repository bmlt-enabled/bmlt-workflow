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
use function PHPUnit\Framework\once;

require_once('config_phpunit.php');


class SubmissionsHandlerTest_my_wp_user
{

    public $ID;
    public $user_login;
    public $user_email;

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

    public $meeting;
    public $bmltwf_dbg;

    protected function setVerboseErrorHandler()
    {
        $handler = function ($errorNumber, $errorString, $errorFile, $errorLine) {
            echo "
ERROR INFO
Message: $errorString
File: $errorFile
Line: $errorLine
";
            print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5));
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

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('wp_json_encode')->justReturn('{"contact_number":"12345","group_relationship":"Group Member","add_contact":"yes","serviceBodyId":2,"additional_info":"my additional info","name":"virtualmeeting randwick","day":2,"startTime":"20:30"}');
        Functions\when('get_site_url')->justReturn('http://127.0.0.1/wordpress');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        Functions\when('__')->returnArg();
        Functions\when('wp_is_json_media_type')->justReturn(true);
        Functions\when('esc_html')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        
        $this->meeting = ' { "id": "3563", "worldId": "", "serviceBodyId": "3", "day": 2, "venueType": 1, "startTime": "19:00", "duration": "01:15", "time_zone": "", "formats": "BT", "lang_enum": "en", "longitude": "0", "latitude": "0", "distance_in_km": "", "distance_in_miles": "", "email_contact": "", "name": "Test Monday Night Meeting", "location_text": "Glebe Town Hall", "location_info": "", "location_street": "160 Johns Road", "location_city_subsection": "", "location_neighborhood": "", "location_municipality": "Glebe", "location_sub_province": "", "location_province": "NSW", "location_postal_code_1": "NSW", "location_nation": "", "comments": "", "train_lines": "", "bus_lines": "", "contact_phone_2": "", "contact_email_2": "", "contact_name_2": "", "contact_phone_1": "", "contact_email_1": "", "contact_name_1": "", "zone": "", "phone_meeting_number": "", "virtual_meeting_link": "", "virtual_meeting_additional_info": "", "published": "1", "root_server_uri": "http:", "formatIds": [3] } ';
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

        $request   = new WP_REST_Request('POST', "http://3.25.141.92/flop/wp-json/bmltwf/v1/submissions/{$test_submission_id}/approve");
        $request->set_header('content-type', 'application/json');
        $request->set_body($json_post);
        $request->set_url_params(array('change_id' => $test_submission_id));
        $request->set_route("/bmltwf/v1/submissions/{$test_submission_id}/approve");
        $request->set_method('POST');

        return $request;
    }

    // private function stub_bmltv3($json_meeting, &$bmlt_input)
    // {
    //     $resp = $json_meeting;
    //     $formats = '[ { "@attributes": { "sequence_index": "0" }, "key_string": "B", "name_string": "Beginners", "description_string": "This meeting is focused on the needs of new members of NA.", "lang": "en", "id": "1", "world_id": "BEG" }, { "@attributes": { "sequence_index": "1" }, "key_string": "BL", "name_string": "Bi-Lingual", "description_string": "This Meeting can be attended by speakers of English and another language.", "lang": "en", "id": "2", "world_id": "LANG" }, { "@attributes": { "sequence_index": "2" }, "key_string": "BT", "name_string": "Basic Text", "description_string": "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.", "lang": "en", "id": "3", "world_id": "BT" }]';
    //     // $bmlt = Mockery::mock('overload:bmltwf\BMLT\Integration');
    //     $bmlt = \Mockery::mock('Integration');

    //     /** @var Mockery::mock $bmlt test */
    //     $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input))
    //         ->shouldReceive('getMeeting')->andreturn(json_decode($resp, true))
    //         ->shouldReceive('getMeetingFormats')->andreturn(json_decode($formats, true))
    //         ->shouldReceive('isAutoGeocodingEnabled')->andreturn(true)
    //         ->shouldReceive('getServiceBodies')->andreturn(array("1"=> array("name"=>"test"),"3"=> array("name"=>"test"),"6"=> array("name"=>"test"),"99"=> array("name"=>"test")));

    //     $bmlt->shouldReceive('geolocateAddress')
    //     ->andReturn([
    //         'results' => [
    //             [
    //                 'geometry' => [
    //                     'location' => [
    //                         'lat' => 37.7749,
    //                         'lng' => -122.4194
    //                     ]
    //                 ]
    //             ]
    //         ]
    //     ]);

    //     Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));
    //     return $bmlt;
    // }

    private function stub_bmltv3($json_meeting, &$bmlt_input)
    {
        $resp = $json_meeting;
        $formats = '[ { "@attributes": { "sequence_index": "0" }, "key_string": "B", "name_string": "Beginners", "description_string": "This meeting is focused on the needs of new members of NA.", "lang": "en", "id": "1", "world_id": "BEG" }, { "@attributes": { "sequence_index": "1" }, "key_string": "BL", "name_string": "Bi-Lingual", "description_string": "This Meeting can be attended by speakers of English and another language.", "lang": "en", "id": "2", "world_id": "LANG" }, { "@attributes": { "sequence_index": "2" }, "key_string": "BT", "name_string": "Basic Text", "description_string": "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.", "lang": "en", "id": "3", "world_id": "BT" }]';
        // $bmlt = Mockery::mock('overload:bmltwf\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input))
            ->shouldReceive('getMeeting')->andreturn(json_decode($resp, true))
            ->shouldReceive('getMeetingFormats')->andreturn(json_decode($formats, true))
            ->shouldReceive('isAutoGeocodingEnabled')->andreturn(true)
            ->shouldReceive('getServiceBodies')->andreturn(array("1" => array("name" => "test"), "3" => array("name" => "test"), "6" => array("name" => "test"), "99" => array("name" => "test")));

        $bmlt->shouldReceive('geolocateAddress')
            ->andReturn([
                'results' => [
                    [
                        'geometry' => [
                            'location' => [
                                'lat' => 37.7749,
                                'lng' => -122.4194
                            ]
                        ]
                    ]
                ]
            ]);

        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));
        return $bmlt;
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_close(): void
    {

        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_close",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "id" => "3277",
                    "serviceBodyId" => "1",
                    "submit" => "Submit Form",
                    "additional_info" => "I'd like to close the meeting please",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
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
    public function test_can_change_name(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return  array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "serviceBodyId" => "6",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "venueType" => 1,
                    "published" => "1"
                );
            }
        };

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

        $resp = '{"id":"3563","worldId":"","serviceBodyId":"6","day":2,"venueType":"1","startTime":"19:00","duration":"01:15","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","formatIds":[3]}';

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($resp, $bmlt_input));
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
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "serviceBodyId" => "1", // changing from 6 to 1
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "published" => "1"

                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'))->set('insert_id', 10);
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\when('wp_mail')->justReturn('true');

        $resp = '{"id":"3563","worldId":"","serviceBodyId":"6","day":2,"venueType":"1","startTime":"19:00","duration":"01:15","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","formatIds":[3]}';

        $bmlt_input = '';

        Functions\when('\get_option')->justReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($resp, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(\WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_change_meeting_format(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "serviceBodyId" => "3",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "formatIds" => ["1"],
                    "venueType" => "1",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "published" => "1"
                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $response =  Mockery::mock('WP_REST_Response');
        $response->shouldReceive('set_status')->andReturn("hello");
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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));

        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_no_starter_kit_requested(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "testing name change",
                    "id" => "3277",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "12345",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "no",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "venueType" => "1",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",

                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
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
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "testing name change",
                    "id" => "3277",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "P85 FG02",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "no",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "venueType" => "1",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                );
            }
        };


        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_with_bad_startTime(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "testing name change",
                    "id" => "3277",
                    "startTime" => "12345",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "12345",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "no",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",

                );
            }
        };

        Functions\when('\get_option')->justReturn("success");
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_with_bad_duration(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return  array(
                    "update_reason" => "reason_new",
                    "name" => "testing name change",
                    "id" => "3277",
                    "startTime" => "10:00:00",
                    "duration" => "9999",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "12345",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "no",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",

                );
            }
        };

        Functions\when('\get_option')->justReturn("success");

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_can_create_new_with_starter_kit_requested(): void
    {

        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "testing name change",
                    "id" => "3277",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "12345",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "yes",
                    "starter_kit_postal_address" => "my house",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "venueType" => "1",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",

                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_format_list_has_garbage(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "formatIds" => ["aeeaetalk", "2", "7", "8", "33", "54", "55"],
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "published" => "1"
                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_too_big(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "day" => "9999",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "published" => "1"

                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_zero(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "day" => "0",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",

                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_change_if_weekday_is_garbage(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "day" => "aerear9",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "published" => "1"
                );
            }
        };

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
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::worldid_publish_to_virtualna
     */
    public function test_worldid_publish_to_virtualna(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $bmlt_input = '';
        $handler = new SubmissionsHandler($this->stub_bmltv3($this->meeting, $bmlt_input));

        $worldid = "12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(true, $worldid);
        $this->assertEquals($new_worldid, "G12345");
        $worldid = "G12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(true, $worldid);
        $this->assertEquals($new_worldid, "G12345");
        $worldid = "U12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(true, $worldid);
        $this->assertEquals($new_worldid, "G12345");
        $worldid = "";
        $new_worldid = $handler->worldid_publish_to_virtualna(true, $worldid);
        $this->assertEquals($new_worldid, "G");
        $worldid = "12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(false, $worldid);
        $this->assertEquals($new_worldid, "U12345");
        $worldid = "U12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(false, $worldid);
        $this->assertEquals($new_worldid, "U12345");
        $worldid = "G12345";
        $new_worldid = $handler->worldid_publish_to_virtualna(false, $worldid);
        $this->assertEquals($new_worldid, "U12345");
        $worldid = "";
        $new_worldid = $handler->worldid_publish_to_virtualna(false, $worldid);
        $this->assertEquals($new_worldid, "U");
        $worldid = "G";
        $new_worldid = $handler->worldid_publish_to_virtualna(false, $worldid);
        $this->assertEquals($new_worldid, "U");
        $worldid = "U";
        $new_worldid = $handler->worldid_publish_to_virtualna(true, $worldid);
        $this->assertEquals($new_worldid, "G");
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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","additional_info":"pls approve","original_name":"Ashfield"}',
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

        $post_change_response = '[{"id":"3563"}]';


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $bmlt_input = '';

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');
        Functions\when('\current_user_can')->justReturn('false');
        Functions\when('\wp_is_json_media_type')->justReturn('true');

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
    public function test_can_approve_new_meeting_with_starter_kit_requested(): void
    {
        $test_submission_id = '14';

        $body = '';

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_new',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","additional_info":"pls approve","original_name":"Ashfield","starter_kit_required":"yes","starter_kit_postal_address":"1 test st"}',
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

        $post_change_response = '[{"id":"3563"}]';


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('createMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $bmlt_input = '';

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        // Functions\when('\wp_mail')->justReturn('true');
        Functions\when('\current_user_can')->justReturn('false');

        Functions\expect('\wp_mail')->once()->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any())->andReturn('true')
            ->once()->with('fso@fso.com', Mockery::any(), Mockery::any(), Mockery::any())->andReturn('true');

        Functions\when('\get_option')->alias(function ($value) {
            if ($value === 'bmltwf_fso_email_address') {
                return "fso@fso.com";
            } elseif ($value === 'bmltwf_fso_feature') {
                return 'display';
            } else {
                return true;
            }
        });

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
    public function test_can_approve_new_meeting(): void
    {
        $test_submission_id = '14';

        $body = '';

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_new',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","additional_info":"pls approve","original_name":"Ashfield"}',
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

        $post_change_response = '[{"id":"3563"}]';


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('createMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $bmlt_input = '';

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');
        Functions\when('\current_user_can')->justReturn('false');

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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_contact":"no","additional_info":"please close this meeting","name":"Ashfield Exodus NA"}'
        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        $retrieve_single_response = $this->meeting;
        $bmlt_input = '';

        Functions\when('\get_option')->justReturn("success");

        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

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
        Functions\when('\current_user_can')->justReturn('false');

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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_contact":"no","additional_info":"please close this meeting","name":"Ashfield Exodus NA"}'
        );

        $retrieve_single_response = $this->meeting;
        $post_change_response = '[{"id":"3563"}]';
        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");

        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);

        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('deleteMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);


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
        Functions\when('\current_user_can')->justReturn('false');

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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","add_contact":"yes","additional_info":"pls approve","original_name":"Ashfield"}',
        );

        $retrieve_single_response = $this->meeting;

        $post_change_response = '[{"id":"3563"}]';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

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
        Functions\when('\current_user_can')->justReturn('false');

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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","add_contact":"yes","additional_info":"pls approve","original_name":"Ashfield"}',
        );

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $post_change_response = '[{"id":"3563"}]';

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
        Functions\when('\current_user_can')->justReturn('false');


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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_contact":"no","additional_info":"please close this meeting","name":"Ashfield Exodus NA"}'
        );

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");



        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make deletemeeting just return true
        $stub_bmltv3->shouldReceive('deleteMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $post_change_response = '[{"id":"3563"}]';

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
        Functions\when('\current_user_can')->justReturn('false');


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
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"group_relationship":"Group Member","add_contact":"no","additional_info":"please close this meeting","name":"Ashfield Exodus NA"}'
        );

        $retrieve_single_response = $this->meeting;

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->prefix = "";

        Functions\when('\get_option')->justReturn("success");


        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        // make updatemeeting just return true
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

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
        Functions\when('\current_user_can')->justReturn('false');


        // $this->debug_log(($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->get_data()['message']);


        // $this->debug_log(($response));
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_time_validation_with_invalid_time(): void
    {
        $form_post = new class{
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "startTime" => "25:00", // Invalid time
                    "name" => "test",
                    "serviceBodyId" => "1"
                );
            }
        };
        
        Functions\when('\get_option')->justReturn("success");
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        
        $integration = Mockery::mock('Integration');
        $integration->shouldReceive('getMeetingFormats')->andReturn([]);
        
        $handler = new SubmissionsHandler($integration);
        $response = $handler->meeting_update_form_handler_rest($form_post);
        
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
     */
    public function test_prevent_double_approval(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('prepare')->andReturn('query');
        $wpdb->shouldReceive('get_row')->andReturn([
            'change_made' => 'approved',
            'change_time' => '2023-01-01 12:00:00'
        ]);
        $wpdb->prefix = "";
        
        $user = new class {
            public $ID = 1;
            public $user_login = 'test';
            public function get($field) {
                return $this->$field ?? null;
            }
        };
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\current_user_can')->justReturn(false);
        
        $handler = new SubmissionsHandler(Mockery::mock('Integration'));
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_url_params')->andReturn(['change_id' => '123']);
        $request->shouldReceive('get_json_params')->andReturn([]);
        $request->shouldReceive('get_param')->with('change_id')->andReturn('123');
        
        $result = $handler->approve_submission_handler($request);
        
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_postal_code_validation(): void
    {
        $form_post = new class{
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "location_postal_code_1" => "INVALID_POSTAL_CODE_TOO_LONG_TO_BE_VALID",
                    "name" => "test",
                    "serviceBodyId" => "1"
                );
            }
        };
        
        Functions\when('\get_option')->justReturn("success");
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        
        $integration = Mockery::mock('Integration');
        $integration->shouldReceive('getMeetingFormats')->andReturn([]);
        
        $handler = new SubmissionsHandler($integration);
        $response = $handler->meeting_update_form_handler_rest($form_post);
        
        // Should succeed as postal code validation is lenient
        $this->assertInstanceOf(WP_Error::class, $response);
    }
    
    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_day_field_correctly_inserted_in_changes_requested(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "Test Day Field Meeting",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "location_postal_code_1" => "12345",
                    "day" => "3", // Wednesday (0-based, so 3 = Thursday)
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "venueType" => "1",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        
        // Capture the inserted data
        $inserted_data = null;
        $wpdb->shouldReceive('insert')->withArgs(function ($table, $data, $format = null) use (&$inserted_data) {
            $inserted_data = $data;
            return true; // Accept any arguments
        })->andReturn(array('0' => '1'))->set('insert_id', 10);
        
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($retrieve_single_response, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        
        // Verify day field is correctly inserted in changes_requested
        $changes_requested = json_decode($inserted_data['changes_requested'], true);
        $this->assertArrayHasKey('day', $changes_requested);
        $this->assertEquals(2, $changes_requested['day']);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_virtualna_published_not_in_f2f(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return  array(
                    "update_reason" => "reason_change",
                    "name" => "testing name change",
                    "id" => "3277",
                    "first_name" => "joe",
                    "last_name" => "joe",
                    "serviceBodyId" => "6",
                    "email_address" => "joe@joe.com",
                    "submit" => "Submit Form",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                    "venueType" => 1,
                    "published" => "1",
                    "virtualna_published" => 1
                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        // handle db insert of submission
        // Capture the inserted data
        $inserted_data = null;
        $wpdb->shouldReceive('insert')->withArgs(function ($table, $data, $format = null) use (&$inserted_data) {
            $inserted_data = $data;
            return true; // Accept any arguments
        })->andReturn(array('0' => '1'))->set('insert_id', 10);
        
        // handle email to service body
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1", "1" => "2"));
        $wpdb->prefix = "";

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        Functions\when('\get_option')->justReturn("success");

        $resp = '{"id":"3563","worldId":"","serviceBodyId":"6","day":2,"venueType":"1","startTime":"19:00","duration":"01:15","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","formatIds":[3]}';

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($resp, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->debug_log(($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $changes_requested = json_decode($inserted_data['changes_requested'], true);

        // print_r($changes_requested);

        $this->assertArrayNotHasKey('virtualna_published', $changes_requested);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_admin_notification_template_conversion(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "Test Meeting",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "first_name" => "John",
                    "last_name" => "Doe",
                    "venueType" => "1",
                    "email_address" => "john@example.com",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'));
        $wpdb->insert_id = 123;
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1"));

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->once()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "admin"));
        
        // Mock wp_mail to capture the email content
        $captured_emails = [];
        Functions\expect('wp_mail')->twice()->withArgs(function($to, $subject, $body, $headers) use (&$captured_emails) {
            $captured_emails[] = array(
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'headers' => $headers
            );
            return true;
        })->andReturn(true);

        // Mock get_option to return our template and other settings
        Functions\when('get_option')->alias(function($option) {
            switch($option) {
                case 'bmltwf_admin_notification_email_template':
                    return '<p>New submission: {field:change_id}</p><p>From: {field:submitter_name} ({field:submitter_email})</p><p>Type: {field:submission_type}</p><p>Service Body: {field:service_body_name}</p><p>Time: {field:submission_time}</p><p>Details: {field:submission}</p><p>Admin URL: {field:admin_url}</p><p>Site: {field:site_name}</p>';
                case 'bmltwf_email_from_address':
                    return 'test@example.com';
                case 'bmltwf_submitter_email_template':
                    return 'submitter template';
                default:
                    return 'success';
            }
        });

        Functions\when('get_bloginfo')->alias(function($info) {
            return $info === 'name' ? 'Test Site' : 'test';
        });

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($this->meeting, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        
        // Verify template fields were substituted correctly
        $this->assertCount(2, $captured_emails, 'Should send 2 emails: admin notification and submitter confirmation');
        
        // Find the admin notification email (sent to admin user)
        $admin_email = null;
        foreach ($captured_emails as $email) {
            if ($email['to'] === 'a@a.com') { // admin user email from mock
                $admin_email = $email;
                break;
            }
        }
        
        $this->assertNotNull($admin_email, 'Admin notification email should be sent');
        $body = $admin_email['body'];
        
        $this->assertStringContainsString('New submission: 123', $body);
        $this->assertStringContainsString('From: John Doe (john@example.com)', $body);
        $this->assertStringContainsString('Type: New Meeting', $body);
        $this->assertStringContainsString('Service Body: test', $body);
        $this->assertStringContainsString('Time: 2022-03-23 09:22:44', $body);
        $this->assertStringContainsString('Site: Test Site', $body);
        $this->assertStringContainsString('Admin URL: http://127.0.0.1/wordpress/wp-admin/admin.php?page=bmltwf-submissions', $body);
        
        // Verify submission details format
        $this->assertStringContainsString('<tr><td>Meeting Name:</td><td>Test Meeting</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Start Time:</td><td>10:00</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Duration:</td><td>01:00</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Location:</td><td>test location</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Street:</td><td>test street</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Municipality:</td><td>test municipality</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Province/State:</td><td>test province</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Meeting Day:</td><td>Monday</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Meeting Formats:</td><td>(BL)-Bi-Lingual </td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Relationship to Group:</td><td>Group Member</td></tr>', $body);
        $this->assertStringContainsString('<tr><td>Add email to meeting:</td><td>Yes</td></tr>', $body);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
     * Test that custom subject lines work for approval emails
     */
    public function test_approve_submission_custom_subject_line(): void
    {
        $test_submission_id = '14';
        $body = '';
        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"Ashfield change name","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","additional_info":"pls approve","original_name":"Ashfield"}',
        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive([
            'prepare' => 'nothing',
            'get_row' => $row,
            'get_results' => 'nothing'
        ]);

        // Mock custom subject template
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_approval_email_subject') return 'Approved: {field:name} - ID {field:change_id}';
            return 'success';
        });

        $retrieve_single_response = $this->meeting;
        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        $stub_bmltv3->shouldReceive('updateMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $post_change_response = '[{"id":"3563"}]';
        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('current_user_can')->justReturn('false');

        // Capture subject to verify custom subject line
        $capturedSubject = null;
        Functions\expect('wp_mail')
            ->once()
            ->with(
                'a@a.com',
                Mockery::capture($capturedSubject),
                Mockery::type('string'),
                Mockery::type('array')
            )
            ->andReturn(true);

        $response = $handlers->approve_submission_handler($request);
        
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        // Verify custom subject line with field substitution
        $this->assertEquals('Approved: Ashfield change name - ID 14', $capturedSubject);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     * Test that custom subject lines work for admin notification emails
     */
    public function test_admin_notification_custom_subject_line(): void
    {
        $form_post = new class {
            public function get_json_params()
            {
                return array(
                    "update_reason" => "reason_new",
                    "name" => "Test Meeting",
                    "startTime" => "10:00:00",
                    "duration" => "01:00:00",
                    "location_text" => "test location",
                    "location_street" => "test street",
                    "location_municipality" => "test municipality",
                    "location_province" => "test province",
                    "day" => "1",
                    "serviceBodyId" => "99",
                    "formatIds" => ["1"],
                    "starter_kit_required" => "no",
                    "first_name" => "John",
                    "last_name" => "Doe",
                    "venueType" => "1",
                    "email_address" => "john@example.com",
                    "group_relationship" => "Group Member",
                    "add_contact" => "yes",
                );
            }
        };

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive('insert')->andReturn(array('0' => '1'));
        $wpdb->insert_id = 123;
        $wpdb->shouldReceive('prepare')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn(array("0" => "1"));

        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->once()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "admin"));
        
        // Mock custom admin subject template
        Functions\when('get_option')->alias(function($option) {
            switch($option) {
                case 'bmltwf_admin_notification_email_subject':
                    return 'New Submission: {field:submitter_name} - {field:name}';
                case 'bmltwf_admin_notification_email_template':
                    return 'Admin template';
                case 'bmltwf_submitter_email_template':
                    return 'Submitter template';
                default:
                    return 'success';
            }
        });

        // Capture emails to verify custom subject line
        $captured_emails = [];
        Functions\expect('wp_mail')->twice()->withArgs(function($to, $subject, $body, $headers) use (&$captured_emails) {
            $captured_emails[] = array(
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'headers' => $headers
            );
            return true;
        })->andReturn(true);

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmltv3($this->meeting, $bmlt_input));
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        
        // Find the admin notification email
        $admin_email = null;
        foreach ($captured_emails as $email) {
            if ($email['to'] === 'a@a.com') {
                $admin_email = $email;
                break;
            }
        }
        
        $this->assertNotNull($admin_email, 'Admin notification email should be sent');
        // Verify custom admin subject line with field substitution
        $this->assertEquals('New Submission: John Doe - Test Meeting', $admin_email['subject']);
    }

    /**
     * @covers bmltwf\REST\Handlers\SubmissionsHandler::approve_submission_handler
     * Test that custom subject lines work for FSO emails
     */
    public function test_fso_email_custom_subject_line(): void
    {
        $test_submission_id = '14';
        $body = '';
        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'change_id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '0000-00-00 00:00:00',
            'changed_by' => 'NULL',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_new',
            'submitter_email' => 'a@a.com',
            'id' => 3563,
            'serviceBodyId' => '4',
            'changes_requested' => '{"name":"New Meeting","day":5,"formatIds":[1,4,8,14,54,55],"group_relationship":"Group Member","additional_info":"pls approve","starter_kit_required":"yes","starter_kit_postal_address":"123 Main St"}',
        );

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive([
            'prepare' => 'nothing',
            'get_row' => $row,
            'get_results' => 'nothing'
        ]);

        $retrieve_single_response = $this->meeting;
        $bmlt_input = '';
        $stub_bmltv3 = $this->stub_bmltv3($retrieve_single_response, $bmlt_input);
        $stub_bmltv3->shouldReceive('createMeeting')->andreturn(true);
        $handlers = new SubmissionsHandler($stub_bmltv3);

        $post_change_response = '[{"id":"3563"}]';
        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('current_user_can')->justReturn('false');

        // Mock custom FSO subject template and settings
        Functions\when('get_option')->alias(function ($value) {
            if ($value === 'bmltwf_fso_email_address') {
                return "fso@fso.com";
            } elseif ($value === 'bmltwf_fso_feature') {
                return 'display';
            } elseif ($value === 'bmltwf_fso_email_subject') {
                return 'Starter Kit Request: {field:name} - {field:submitter_name}';
            } else {
                return 'success';
            }
        });

        // Capture emails to verify custom FSO subject line
        $captured_emails = [];
        Functions\expect('wp_mail')->twice()->withArgs(function($to, $subject, $body, $headers) use (&$captured_emails) {
            $captured_emails[] = array(
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'headers' => $headers
            );
            return true;
        })->andReturn(true);

        $response = $handlers->approve_submission_handler($request);
        
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        
        // Find the FSO email
        $fso_email = null;
        foreach ($captured_emails as $email) {
            if ($email['to'] === 'fso@fso.com') {
                $fso_email = $email;
                break;
            }
        }
        
        $this->assertNotNull($fso_email, 'FSO email should be sent');
        // Verify custom FSO subject line with field substitution
        $this->assertEquals('Starter Kit Request: New Meeting - test submitter', $fso_email['subject']);
    }


}
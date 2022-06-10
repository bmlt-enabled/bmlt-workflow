<?php


declare(strict_types=1);

// debug settings
use wbw\WBW_Debug;

use wbw\REST\Handlers\SubmissionsHandler;

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

        $this->meeting = <<<EOD
    {
        "id_bigint": "3563",
        "worldid_mixed": "",
        "shared_group_id_bigint": "",
        "service_body_bigint": "3",
        "weekday_tinyint": "2",
        "venue_type": "1",
        "start_time": "19:00:00",
        "duration_time": "01:15:00",
        "time_zone": "",
        "formats": "BT",
        "lang_enum": "en",
        "longitude": "0",
        "latitude": "0",
        "distance_in_km": "",
        "distance_in_miles": "",
        "email_contact": "",
        "meeting_name": "Test Monday Night Meeting",
        "location_text": "Glebe Town Hall",
        "location_info": "",
        "location_street": "160 Johns Road",
        "location_city_subsection": "",
        "location_neighborhood": "",
        "location_municipality": "Glebe",
        "location_sub_province": "",
        "location_province": "NSW",
        "location_postal_code_1": "NSW",
        "location_nation": "",
        "comments": "",
        "train_lines": "",
        "bus_lines": "",
        "contact_phone_2": "",
        "contact_email_2": "",
        "contact_name_2": "",
        "contact_phone_1": "",
        "contact_email_1": "",
        "contact_name_1": "",
        "zone": "",
        "phone_meeting_number": "",
        "virtual_meeting_link": "",
        "virtual_meeting_additional_info": "",
        "published": "1",
        "root_server_uri": "http:",
        "format_shared_id_list": "3"
    }
    EOD;

        $this->wbw_dbg = new WBW_Debug();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();

        unset($this->wbw_dbg);
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

    private function stub_bmlt($json_meeting, &$bmlt_input)
    {
        $resp = $json_meeting;
        $formats = '[ { "@attributes": { "sequence_index": "0" }, "key_string": "B", "name_string": "Beginners", "description_string": "This meeting is focused on the needs of new members of NA.", "lang": "en", "id": "1", "world_id": "BEG" }, { "@attributes": { "sequence_index": "1" }, "key_string": "BL", "name_string": "Bi-Lingual", "description_string": "This Meeting can be attended by speakers of English and another language.", "lang": "en", "id": "2", "world_id": "LANG" }, { "@attributes": { "sequence_index": "2" }, "key_string": "BT", "name_string": "Basic Text", "description_string": "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.", "lang": "en", "id": "3", "world_id": "BT" }]';
        // $bmlt = Mockery::mock('overload:wbw\BMLT\Integration');
        $bmlt = \Mockery::mock('Integration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postAuthenticatedRootServerRequest' => $resp])->with('', \Mockery::capture($bmlt_input))
            ->shouldReceive('geolocateAddress')->andreturn(array("latitude" => 1, "longitude" => 1))
            ->shouldReceive('retrieve_single_meeting')->andreturn(json_decode($resp, true))
            ->shouldReceive('getMeetingFormats')->andreturn(json_decode($formats, true));

        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));
        return $bmlt;
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');


        $retrieve_single_response = $this->meeting;
        $this->wbw_dbg->debug_log("THISMEETING");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($retrieve_single_response));

        $bmlt_input = '';
        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->wbw_dbg->debug_log("TEST RESPONSE");
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        // $this->wbw_dbg->debug_log($email_addresses);
        // $this->assertEquals($email_addresses,'a@a.com,a@a.com');
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $resp = '{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"6","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}';

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($resp, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($resp, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(\WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
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

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        
        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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

        $handlers = new SubmissionsHandler();
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

        /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
     */
    public function test_cant_create_new_with_bad_duration_ime(): void
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
        $handlers = new SubmissionsHandler();
        $response = $handlers->meeting_update_form_handler_rest($form_post);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('get_user_by')->with(Mockery::any(), Mockery::any())->twice()->andReturn(new SubmissionsHandlerTest_my_wp_user(2, "test test"));
        Functions\when('wp_mail')->justReturn('true');

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input), $WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
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

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }


    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('wp_mail')->never();

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('wp_mail')->never();

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('wp_mail')->never();

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
        $response = $handlers->meeting_update_form_handler_rest($form_post);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\REST\Handlers\SubmissionsHandler::meeting_update_form_handler_rest
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
        Functions\expect('wp_mail')->never();

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);
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

        $retrieve_single_response = $this->meeting;

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $post_change_response = '[{"id_bigint":"3563"}]';

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // 
        // $this->wbw_dbg->debug_log('APPROVEREQUEST');

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
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

        $resp = $this->meeting;
        $bmlt_input = '';

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($resp, $bmlt_input),$WBW_WP_Options);

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

        $user = new SubmissionsHandlerTest_my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // 
        // $this->wbw_dbg->debug_log($this->wbw_dbg->debug_log("BMLT INPUT"));
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($bmlt_input));

        $this->assertArrayHasKey("set_meeting_change", $bmlt_input);
        $this->assertArrayNotHasKey("delete_meeting", $bmlt_input);

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
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

        $retrieve_single_response = $this->meeting;
        $post_change_response = '[{"id_bigint":"3563"}]';
        $bmlt_input = '';

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);


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
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        $this->assertArrayHasKey("delete_meeting", $bmlt_input);
        $this->assertArrayNotHasKey("set_meeting_change", $bmlt_input);

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
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

        $retrieve_single_response = $this->meeting;

        $post_change_response = '[{"id_bigint":"3563"}]';

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $bmlt_input = '';
        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);

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
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $handlers->approve_submission_handler($request);
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

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';

        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);

        $post_change_response = '[{"id_bigint":"3563"}]';

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
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);


        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $handlers->approve_submission_handler($request);
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

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';


        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);

        $post_change_response = '[{"id_bigint":"3563"}]';

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
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
        $this->assertEquals($bmlt_input, array("bmlt_ajax_callback" => 1, "delete_meeting" => 3563));
        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
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

        $retrieve_single_response = $this->meeting;

        $bmlt_input = '';
        $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
        /** @var Mockery::mock $WBW_WP_Options test */
        $WBW_WP_Options->shouldReceive('wbw_get_option')->andReturn("success");

        $handlers = new SubmissionsHandler($this->stub_bmlt($retrieve_single_response, $bmlt_input),$WBW_WP_Options);

        $post_change_response = '[{"report":"3563", "success":"true"}]';

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
        Functions\when('\wp_remote_retrieve_body')->justReturn($post_change_response);
        Functions\when('\wp_mail')->justReturn('true');


        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($request));

        $response = $handlers->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        $this->assertArrayHasKey("set_meeting_change", $bmlt_input);
        $this->assertArrayNotHasKey("delete_meeting", $bmlt_input);

        // $this->wbw_dbg->debug_log($this->wbw_dbg->vdump($response));
    }
}

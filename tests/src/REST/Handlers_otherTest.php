<?php

declare(strict_types=1);

use wbw\Debug;
use wbw\REST\Handlers;
use wbw\BMLT\Integration;

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

class my_wp_user
{
    public function __construct($id, $name)
    {
        $this->ID = $id;
        $this->user_login = $name;
    }

    public function get()
    {
        return $this->ID;
    }
    // public function user_login()
    // {
    //     return $this->name;
    // }
}
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

final class HandlersTest extends TestCase
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
        $basedir = dirname(dirname(dirname(dirname(__FILE__))));
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')){
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\apply_filters')->returnArg(2);
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('\absint')->returnArg();
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

        if (!defined('CONST_OTHER_SERVICE_BODY')) {
            define('CONST_OTHER_SERVICE_BODY', '99999999999');
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', '99999999999');
        }
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
     * @covers ::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new Handlers($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    /**
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new Handlers($bmlt);

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
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new Handlers($bmlt);

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
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);

        $rest = new Handlers($bmlt);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
    }


    /**
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);

        $rest = new Handlers($bmlt);

        Functions\expect('\wp_mail')->times(1)->with('a@a.com', Mockery::any(), Mockery::any(), Mockery::any());

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
    }

    /**
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new Handlers($bmlt);

        // $wbw_dbg->debug_log($wbw_dbg->vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);
        $this->assertEquals($bmlt_input, array("bmlt_ajax_callback" => 1, "delete_meeting" => 3563));
        // $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    }

    /**
     * @covers wbw\REST\Handlers::approve_submission_handler
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

        $user = new my_wp_user(1, 'username');
        Functions\when('\wp_get_current_user')->justReturn($user);
        Functions\when('\is_wp_error')->justReturn(false);
        Functions\when('\wp_remote_retrieve_body')->justReturn($resp);
        Functions\when('\wp_mail')->justReturn('true');

        $rest = new Handlers($bmlt);

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

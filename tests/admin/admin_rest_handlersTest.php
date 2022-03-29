<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

define('ABSPATH', '99999999999');

// We require the file we need to test.
// require 'admin/admin_rest_handlers.php';
require 'admin/admin_rest_handlers.php';

if (!(function_exists('vdump'))) {
    function vdump($object)
    {
        ob_start();
        var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
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
final class admin_rest_handlersTest extends TestCase
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
        // $this->setVerboseErrorHandler();
        $basedir = dirname(dirname(dirname(__FILE__)));
        require($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');

        Functions\when('wp_json_encode')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('absint')->returnArg();

        if (!defined('CONST_OTHER_SERVICE_BODY')) {
            define('CONST_OTHER_SERVICE_BODY', '99999999999');
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', '99999999999');
        }
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

    public function test_can_approve_change_meeting(): void
    {
        $test_submission_id = '14';

        $body = array("action_message" => "hello there");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '2022-03-25 11:37:51',
            'changed_by' => 'test',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_change',
            'submitter_email' => 'a@a.com',
            'meeting_id' => NULL,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_id":"3563","weekday_tinyint":"6","format_shared_id_list":"32,56","original_meeting_name":"Annandale Thur - Spiritual Principle a Day"}',
            'action_message' => 'hi'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"1","root_server_uri":"http:","format_shared_id_list":"3"}]';
        $bmlt = Mockery::mock('overload:BMLTIntegration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postConfiguredRootServerRequest' => $resp]);

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
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn($resp);

        $rest = new wbw_submissions_rest_handlers();

        // error_log(vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // error_log(vdump($response));
    }

    public function test_can_approve_close_meeting_with_unpublish(): void
    {
        $test_submission_id = '14';

        $body = array("action_message" => "hello there");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '2022-03-25 11:37:51',
            'changed_by' => 'test',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => NULL,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_id":3563,"additional_info":"please close this is shut","group_relationship":"Area Trusted Servant","meeting_name":"90 in 90 (Burwood) update"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        $bmlt = Mockery::mock('overload:BMLTIntegration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postConfiguredRootServerRequest' => $resp]);

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
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn($resp);

        $rest = new wbw_submissions_rest_handlers();

        // error_log(vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // error_log(vdump($response));
    }

    public function test_can_approve_close_meeting_with_delete(): void
    {
        $test_submission_id = '14';

        $body = array("action_message" => "hello there", "delete" => "true");

        $request = $this->generate_approve_request($test_submission_id, $body);

        $row = array(
            'id' => $test_submission_id,
            'submission_time' => '2022-03-23 09:25:53',
            'change_time' => '2022-03-25 11:37:51',
            'changed_by' => 'test',
            'change_made' => 'NULL',
            'submitter_name' => 'test submitter',
            'submission_type' => 'reason_close',
            'submitter_email' => 'a@a.com',
            'meeting_id' => NULL,
            'service_body_bigint' => '4',
            'changes_requested' => '{"meeting_id":3563,"additional_info":"please close this is shut","group_relationship":"Area Trusted Servant","meeting_name":"90 in 90 (Burwood) update"}'
        );

        $resp = '[{"id_bigint":"3563","worldid_mixed":"","shared_group_id_bigint":"","service_body_bigint":"3","weekday_tinyint":"2","venue_type":"1","start_time":"19:00:00","duration_time":"01:15:00","time_zone":"","formats":"BT","lang_enum":"en","longitude":"0","latitude":"0","distance_in_km":"","distance_in_miles":"","email_contact":"","meeting_name":"Test Monday Night Meeting","location_text":"Glebe Town Hall","location_info":"","location_street":"160 Johns Road","location_city_subsection":"","location_neighborhood":"","location_municipality":"Glebe","location_sub_province":"","location_province":"NSW","location_postal_code_1":"NSW","location_nation":"","comments":"","train_lines":"","bus_lines":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","zone":"","phone_meeting_number":"","virtual_meeting_link":"","virtual_meeting_additional_info":"","published":"0","root_server_uri":"http:","format_shared_id_list":"3"}]';
        $bmlt = Mockery::mock('overload:BMLTIntegration');

        /** @var Mockery::mock $bmlt test */
        $bmlt->shouldReceive(['postConfiguredRootServerRequest' => $resp]);

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
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->justReturn($resp);

        $rest = new wbw_submissions_rest_handlers();

        // error_log(vdump($request));

        $response = $rest->approve_submission_handler($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('Approved submission id 14', $response->data['message']);

        // error_log(vdump($response));
    }
}

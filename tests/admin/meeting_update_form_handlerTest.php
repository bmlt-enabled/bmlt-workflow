<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

// We require the file we need to test.
require 'admin/meeting_update_form_handler.php';


final class meeting_update_form_handlerTest extends TestCase
{

    protected function setUp(): void
    {
        $basedir = dirname( dirname( dirname( __FILE__ ) ) );
        // echo $basedir;
        require( $basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once( $basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php' );
        require_once( $basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php' );
        require_once( $basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php' );
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('get_option')->returnArg();
        Functions\when('current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('wp_json_encode')->returnArg();
        if (!defined('CONST_OTHER_SERVICE_BODY'))
        {
            define('CONST_OTHER_SERVICE_BODY','99999999999');
        }

    }
    
    public function testCanCloseMeeting():void {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_close",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "meeting_id" => "3522",
            "submit" => "Submit Form",
            "additional_info" => "I'd like to close the meeting please"
        );

        global $wpdb;
        $wpdb =  Mockery::mock( 'wpdb' );
        $wpdb->shouldReceive( 'insert' )->andReturn( array('0'=>'1') )->set('insert_id',10);
        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
    
        $response = meeting_update_form_handler_rest($form_post);
        var_dump($response);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
     // this is for wp_error
        // $this->assertEquals(200, $response->get_error_data()['status']);
    }

    public function testCanRequestOther():void {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_other",
            "other_reason" => "testing other",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form"
        );

        global $wpdb;
        $wpdb = Mockery::mock( 'wpdb' );
        $wpdb->shouldReceive( 'insert' )->andReturn( array('0'=>'1') )->set('insert_id',10);
        $response = meeting_update_form_handler_rest($form_post);
        var_dump($response);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function testChangeMeetingCanChangeMeetingName():void {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_change",
            "meeting_name" => "testing name change",
            "meeting_id" => "200",
            "first_name" => "joe",
            "last_name" => "joe",
            "email_address" => "joe@joe.com",
            "submit" => "Submit Form"
        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        Functions\when('curl_exec')->justReturn($json);
        // redefine('time', always(strtotime('Dec 31, 1999')));
        // echo date('Y-m-d', time());
        global $wpdb;
        $wpdb = Mockery::mock( 'wpdb' );
        $wpdb->shouldReceive( 'insert' )->andReturn( array('0'=>'1') )->set('insert_id',10);
        $response = meeting_update_form_handler_rest($form_post);
         var_dump($response);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function testNewMeetingCanCreateWithNoStarterKit():void {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "200",
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
            "submit" => "Submit Form"
        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        global $wpdb;
        $wpdb = Mockery::mock( 'wpdb' );
        $wpdb->shouldReceive( 'insert' )->andReturn( array('0'=>'1') )->set('insert_id',10);
        $response = meeting_update_form_handler_rest($form_post);
         var_dump($response);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function testNewMeetingCantCreateIfStarterKitMissing():void {

        $form_post = array(
            "action" => "meeting_update_form_response",
            "update_reason" => "reason_new",
            "meeting_name" => "testing name change",
            "meeting_id" => "200",
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
            "submit" => "Submit Form"
        );

        $json = '[{"id_bigint":"3277","worldid_mixed":"OLM297","service_body_bigint":"6","weekday_tinyint":"3","venue_type":"2","start_time":"19:00:00","duration_time":"01:00:00","time_zone":"","formats":"JT,LC,VM","longitude":"151.2437","latitude":"-33.9495","meeting_name":"Online Meeting - Maroubra Nightly","location_text":"Online","location_info":"","location_street":"","location_neighborhood":"","location_municipality":"Maroubra","location_sub_province":"","location_province":"NSW","location_postal_code_1":"2035","comments":"","contact_phone_2":"","contact_email_2":"","contact_name_2":"","contact_phone_1":"","contact_email_1":"","contact_name_1":"","virtual_meeting_additional_info":"By phone 02 8015 6011Meeting ID: 83037287669 Passcode: 096387","root_server_uri":"http://54.153.167.239/main_server","format_shared_id_list":"14,40,54"}]';
        global $wpdb;
        $wpdb = Mockery::mock( 'wpdb' );
        $wpdb->shouldReceive( 'insert' )->andReturn( array('0'=>'1') )->set('insert_id',10);
        $response = meeting_update_form_handler_rest($form_post);
         var_dump($response);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

}
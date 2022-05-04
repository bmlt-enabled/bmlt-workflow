<?php

declare(strict_types=1);

use wbw\Debug;
use wbw\BMLT\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

global $wbw_dbg;
$wbw_dbg = new Debug;


/**
 * @covers wbw\BMLT\Integration
 * @uses wbw\Debug
 */
final class IntegrationTest extends TestCase
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
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_can_call_testServerAndAuth_with_success(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('200');
        Functions\when('wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertTrue($response);
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_cant_call_testServerAndAuth_with_invalid_server(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('403');
        Functions\when('wp_remote_retrieve_body')->justReturn('<html></html>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::testServerAndAuth
     */
    public function test_cant_call_testServerAndAuth_with_invalid_login(): void
    {
        // testServerAndAuth($username, $password, $server)

        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_response_code')->justReturn('200');
        Functions\when('wp_remote_retrieve_body')->justReturn('</head><body class="admin_body"><h2 class="c_comdef_not_auth_3">There was a problem with the user name or password that you entered.</h2><div class="c_comdef_admin_login_form_container_div"><noscript>');
        Functions\when('http_build_query')->justReturn(1);

        $integration = new Integration();
        $response = $integration->testServerAndAuth("user", "pass", "server");
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingFormats
     */
    public function test_can_call_getMeetingFormats(): void
    {
        //     public function getMeetingFormats()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration();

        $response = $integration->getMeetingFormats();
        $this->assertIsArray($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingFormats
     */
    public function test_cant_call_getMeetingFormats_with_invalid_bmlt_details(): void
    {
        //     public function getMeetingFormats()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration();

        $response = $integration->getMeetingFormats();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT"}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

        $integration = new Integration();

        $response = $integration->getMeetingStates();
        $this->assertIsArray($response);
        $this->assertEquals(array("MA", "ME", "NH", "RI", "VT"), $response);
    }
    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_can_call_getMeetingStates_with_no_states_defined(): void
    {
        //         public function getMeetingStates()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": ""}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

        $integration = new Integration();

        $response = $integration->getMeetingStates();
        $this->assertFalse($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingStates
     */
    public function test_cant_call_getMeetingStates_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingStates()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{"key_string": "B","name_string": "Beginners","description_string": "This meeting is focused on the needs of new members of NA.","lang": "en","id": "1","world_id": "BEG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"},{"key_string": "BL","name_string": "Bi-Lingual","description_string": "This meeting is conducted in both English and another language.","lang": "en","id": "2","world_id": "LANG","root_server_uri": "https://brucegardner.net/bmlt-root-server-master/main_server","format_type_enum": "FC3"}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration();

        $response = $integration->getMeetingStates();
        $this->assertInstanceOf(WP_Error::class, $response);
    }


    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_can_call_getMeetingCounties_with_counties_defined(): void
    {
        //         public function getMeetingCounties()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": "Androscoggin,Aroostook,Barnstable,Belknap"}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

        $integration = new Integration();

        $response = $integration->getMeetingCounties();
        $this->assertIsArray($response);
        $this->assertEquals("Androscoggin", $response[0]);
    }
    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_can_call_getMeetingCounties_with_no_counties_defined(): void
    {
        //         public function getMeetingCounties()


        Functions\when('wp_remote_retrieve_body')->justReturn('[{"changesPerMeeting": "5","meeting_states_and_provinces": "MA,ME,NH,RI,VT","meeting_counties_and_sub_provinces": ""}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();

        $integration = new Integration();

        $response = $integration->getMeetingCounties();
        $this->assertFalse($response);
    }

    /**
     * @covers wbw\BMLT\Integration::getMeetingCounties
     */
    public function test_cant_call_getMeetingCounties_with_invalid_bmlt_details(): void
    {
        //         public function getMeetingCounties()

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration();

        $response = $integration->getMeetingCounties();
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_can_call_postAuthenticatedRootServerRequest_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequest('test', array('args'=>'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequest()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequest('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequest
     */
    public function test_cant_call_postAuthenticatedRootServerRequest_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequest($url, $postargs)

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequest('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

        /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_can_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array('args'=>'args'));
        $this->assertIsString($response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_valid_auth_no_args(): void
    {
        //         public function postAuthenticatedRootServerRequestSemantic()

        Functions\when('\wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('\wp_safe_remote_post')->returnArg();
        Functions\when('\wp_remote_retrieve_cookies')->justReturn(array("0" => "1"));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', null);
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::postAuthenticatedRootServerRequestSemantic
     */
    public function test_cant_call_postAuthenticatedRootServerRequestSemantic_with_invalid_bmlt_details(): void
    {
        //             public function postAuthenticatedRootServerRequestSemantic($url, $postargs)

        Functions\when('wp_remote_retrieve_body')->justReturn('[{}]');
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->justReturn(new \WP_Error(1));

        $integration = new Integration();

        $response = $integration->postAuthenticatedRootServerRequestSemantic('test', array("arg1" => "args1"));
        $this->assertInstanceOf(WP_Error::class, $response);
    }

    /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_can_call_geolocateAddress_with_valid_address(): void
    {
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = '{ "results" : [ { "address_components" : [ { "long_name" : "Sydney", "short_name" : "Sydney", "types" : [ "colloquial_area", "locality", "political" ] }, { "long_name" : "New South Wales", "short_name" : "NSW", "types" : [ "administrative_area_level_1", "political" ] }, { "long_name" : "Australia", "short_name" : "AU", "types" : [ "country", "political" ] } ], "formatted_address" : "Sydney NSW, Australia", "geometry" : { "bounds" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } }, "location" : { "lat" : -33.8688197, "lng" : 151.2092955 }, "location_type" : "APPROXIMATE", "viewport" : { "northeast" : { "lat" : -33.5781409, "lng" : 151.3430209 }, "southwest" : { "lat" : -34.118347, "lng" : 150.5209286 } } }, "partial_match" : true, "place_id" : "ChIJP3Sa8ziYEmsRUKgyFmh9AQM", "types" : [ "colloquial_area", "locality", "political" ] } ], "status" : "OK" }';

        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=sydney%2C+australia&&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $integration = new Integration(true);
        $response = $integration->geolocateAddress('sydney, australia');
        global $wbw_dbg;
        $wbw_dbg->debug_log("*** GEO RESPONSE");
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertIsNumeric($response['latitude']);
        $this->assertIsNumeric($response['longitude']);

    }

        /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_cant_call_geolocateAddress_with_invalid_address(): void
    {
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = ' { "results" : [], "status" : "ZERO_RESULTS" }';
       
        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=junk%2C+junk&&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $integration = new Integration(true);
        $response = $integration->geolocateAddress('junk, junk');
        global $wbw_dbg;
        $wbw_dbg->debug_log("*** GEO RESPONSE");
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_Error::class, $response);

    }

       /**
     * @covers wbw\BMLT\Integration::geolocateAddress
     * @covers wbw\BMLT\Integration::getGmapsKey
     * @covers wbw\BMLT\Integration::AuthenticateRootServer
     * @covers wbw\BMLT\Integration::post
     * @covers wbw\BMLT\Integration::get
     */
    public function test_error_when_gmaps_call_returns_trash(): void
    {
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_get')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
        Functions\when('wp_remote_retrieve_cookies')->returnArg();
        Functions\when('wp_remote_retrieve_body')->justReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> <head> <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> <meta http-equiv="content-type" content="text/html; charset=utf-8" /> <meta http-equiv="Content-Script-Type" content="text/javascript" /> <meta http-equiv="Content-Style-Type" content="text/css" /> <link rel="stylesheet" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/styles.css?v=1650950537" /> <link rel="icon" href="https://brucegardner.net/bmlt-root-server-master/main_server/local_server/server_admin/style/images/shortcut.png" /> <link rel="preconnect" href="https://fonts.gstatic.com"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet"> <title>Basic Meeting List Toolbox Administration Console</title> </head> <body class="admin_body"> <div class="bmlt_admin_logout_bar"><h4><a href="/bmlt-root-server-master/main_server/index.php?admin_action=logout">Sign Out (Server Administrator)</a></h4><div class="server_version_display_div"> 2.16.5 </div></div><div id="google_maps_api_error_div" class="bmlt_admin_google_api_key_error_bar item_hidden"><h4><a id="google_maps_api_error_a" href="https://bmlt.app/google-api-key/" target="_blank"></a></h4></div><div class="admin_page_wrapper"><div id="bmlt_admin_main_console" class="bmlt_admin_main_console_wrapper_div"> <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=googlemapstestkey&libraries=geometry"></script><script type="text/javascript">var my_localized_strings = {"default_meeting_published":true,"week_starts_on":0,"name":"English","enum":"en","comdef_map_radius_ranges":[0.0625,0.125,0.1875,0.25,0.4375,0.5,0.5625,0.75,0.8125,1,1.25,1.5,1.75,2,2.25,2.5,2.75,3,3.25,3.5,3.75,4,4.25,4.5,4.75,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10,11,12,13,14,15,17.5,20,22.5,25,27.5,30,35,40,45,50,60,70,80,90,100,150,200],"include_service_body_email_in_semantic":false,"auto_geocoding_enabled":true,"zip_auto_geocoding_enabled":false,"county_auto_geocoding_enabled":false,"sort_formats":true,"meeting_counties_and_sub_provinces":[],"meeting_states_and_provinces":[],"google_api_key":"googlemapstestkey","dbPrefix":"na","region_bias":"au","default_duration_time":"1:30:00","default_minute_interval":5,"search_spec_map_center":{"longitude":-118.563659,"latitude":34.235918,"zoom":6},"change_type_strings":{"__THE_MEETING_WAS_CHANGED__":"The meeting was changed.","__THE_MEETING_WAS_CREATED__":"The meeting was created.","__THE_MEETING_WAS_DELETED__":"The meeting was deleted.","__THE_MEETING_WAS_ROLLED_BACK__":"The meeting was rolled back to a previous version.","__THE_FORMAT_WAS_CHANGED__":"The format was changed.","__THE_FORMAT_WAS_CREATED__":"The format was created.","__THE_FORMAT_WAS_DELETED__":"The format was deleted.","__THE_FORMAT_WAS_ROLLED_BACK__":"The format was rolled back to a previous version.","__THE_SERVICE_BODY_WAS_CHANGED__":"The service body was changed.","__THE_SERVICE_BODY_WAS_CREATED__":"The service body was created.","__THE_SERVICE_BODY_WAS_DELETED__":"The service body was deleted.","__THE_SERVICE_BODY_WAS_ROLLED_BACK__":"The service body was rolled back to a previous version.","__THE_USER_WAS_CHANGED__":"The user was changed.","__THE_USER_WAS_CREATED__":"The user was created.","__THE_USER_WAS_DELETED__":"The user was deleted.","__THE_USER_WAS_ROLLED_BACK__":"The user was rolled back to a previous version.","__BY__":"by","__FOR__":"for"},"detailed_change_strings":{"was_changed_from":"was changed from","to":"to","was_changed":"was changed","was_added_as":"was added as","was_deleted":"was deleted","was_published":"The meeting was published","was_unpublished":"The meeting was unpublished","formats_prompt":"The meeting format","duration_time":"The meeting duration","start_time":"The meeting start time","longitude":"The meeting longitude","latitude":"The meeting latitude","sb_prompt":"The meeting changed its Service Body from",');

        $json = ' { "junk" : "junk" }';
       
        Functions\when('curl_exec')->justReturn($json);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=junk%2C+junk&&key=googlemapstestkey";

        Functions\expect('curl_init')->once()->with($url);
        // Functions\expect('curl_init')->once();
        Functions\when('curl_setopt')->returnArg();
        Functions\when('curl_close')->returnArg();
    
        $integration = new Integration(true);
        $response = $integration->geolocateAddress('junk, junk');
        global $wbw_dbg;
        $wbw_dbg->debug_log("*** GEO RESPONSE");
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertInstanceOf(WP_Error::class, $response);

    }

}

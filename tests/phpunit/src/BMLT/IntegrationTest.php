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

}

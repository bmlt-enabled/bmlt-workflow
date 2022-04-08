<?php

declare(strict_types=1);

use wbw\Debug;
use wbw\REST\Handlers\ServiceBodiesHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

global $wbw_dbg;
$wbw_dbg = new Debug;

/**
 * @covers wbw\REST\Handlers\ServiceBodiesHandler
 * @uses wbw\Debug
 * @uses wbw\REST\HandlerCore
 */
final class ServiceBodiesHandlerTest extends TestCase
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

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\apply_filters')->returnArg(2);
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('\absint')->returnArg();
        Functions\when('\get_option')->returnArg();
        Functions\when('wp_safe_remote_post')->returnArg();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers wbw\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
     */
    public function test_can_get_service_bodies_simple_with_success(): void
    {

        global $wbw_dbg;
        $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/servicebodies");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/servicebodies");
        $request->set_method('GET');

        $sblookup = array(
            '0' => array(
                "service_body_bigint" => "2",
                "service_area_name" => "Sydney Metro",
                "contact_email" => "",
                "show_on_form" => "1"
            ),
            '1' => array(
                "service_body_bigint" =>"3",
                "service_area_name" =>"Sydney North",
                "contact_email" =>"",
                "show_on_form" =>"1"
            ),
            '2' => array(
                "service_body_bigint" => "4",
                "service_area_name" =>"Sydney South",
                "contact_email" =>"",
                "show_on_form" =>"1"
            )
        );
        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('get_results')->andReturn($sblookup);

        $rest = new ServiceBodiesHandler();

        $response = $rest->get_service_bodies_handler($request);

        $wbw_dbg->debug_log($wbw_dbg->vdump($response));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $wbw_dbg->debug_log($wbw_dbg->vdump($response));
        $this->assertEquals($response->get_data()['2']['name'], 'Sydney Metro');
    }


    // /**
    //  * @covers wbw\REST\Handlers\ServiceBodiesHandler::get_service_bodies_handler
    //  */
    // public function test_can_get_service_bodies_detail_with_success(): void
    // {

    //     global $wbw_dbg;
    //     $request = new WP_REST_Request('GET', "http://54.153.167.239/flop/wp-json/wbw/v1/servicebodies");
    //     $request->set_header('content-type', 'application/json');
    //     $request->set_route("/wbw/v1/servicebodies");
    //     $request->set_method('GET');
    //     $request->set_param('detail','true');

    //     Functions\when('\current_user_can')->justReturn(true);
    //     Functions\when('\wp_remote_retrieve_body')->justReturn('<html></html');

    //     $sblookup = array(
    //         '0' => array(
    //             "service_body_bigint" => "2",
    //             "service_area_name" => "Sydney Metro",
    //             "contact_email" => "",
    //             "show_on_form" => "1"
    //         ),
    //         '1' => array(
    //             "service_body_bigint" =>"3",
    //             "service_area_name" =>"Sydney North",
    //             "contact_email" =>"",
    //             "show_on_form" =>"1"
    //         ),
    //         '2' => array(
    //             "service_body_bigint" => "4",
    //             "service_area_name" =>"Sydney South",
    //             "contact_email" =>"",
    //             "show_on_form" =>"0"
    //         )
    //     );
    //     global $wpdb;
    //     $wpdb =  Mockery::mock('wpdb');
    //     /** @var Mockery::mock $wpdb test */
    //     $wpdb->shouldReceive('get_results')->andReturn($sblookup);

    //     $rest = new ServiceBodiesHandler();

    //     $response = $rest->get_service_bodies_handler($request);

    //     $wbw_dbg->debug_log($wbw_dbg->vdump($response));

    //     $this->assertInstanceOf(WP_REST_Response::class, $response);
    //     $wbw_dbg->debug_log($wbw_dbg->vdump($response));
    //     $this->assertEquals($response->get_data()['2']['name'], 'Sydney Metro');
    // }

}

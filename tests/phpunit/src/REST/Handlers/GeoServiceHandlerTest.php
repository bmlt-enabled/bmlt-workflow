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

use bmltwf\REST\Handlers\GeoServiceHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
require_once('config_phpunit.php');


/**
 * @covers bmltwf\REST\Handlers\GeoServiceHandler
 * @uses bmltwf\BMLTWF_Debug
 * @uses bmltwf\BMLTWF_Secrets
 * @uses bmltwf\REST\HandlerCore
 * @uses bmltwf\BMLT\Integration
 */
final class GeoServiceHandlerTest extends TestCase
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
        };
        set_error_handler($handler);
    }

    protected function setUp(): void
    {
        $this->setVerboseErrorHandler();
        $basedir = getcwd();

        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/autoload.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-request.php');

        Brain\Monkey\setUp();

        Functions\when('\wp_json_encode')->returnArg();
        Functions\when('\current_time')->justReturn('2022-03-23 09:22:44');
        Functions\when('__')->returnArg();
        Functions\when('\home_url')->justReturn('https://example.com');
        Functions\when('\get_option')->justReturn('');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers bmltwf\REST\Handlers\GeoServiceHandler::post_geoservice_handler
     */
    public function test_post_geoservice_success(): void
    {
        $request = new WP_REST_Request('POST', '/bmltwf/v1/geoservice');
        $request['bmltwf_geo_service_key'] = 'geo_abc123';

        $Intstub = \Mockery::mock('Integration');
        $Intstub->shouldReceive('geoServiceBaseUrl')->andReturn('https://geo.aws.bmlt.app');
        $Intstub->shouldReceive('testGeoService')->andReturn(200);

        $rest = new GeoServiceHandler($Intstub);
        $response = $rest->post_geoservice_handler($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * @covers bmltwf\REST\Handlers\GeoServiceHandler::post_geoservice_handler
     */
    public function test_post_geoservice_empty_key_fails(): void
    {
        $request = new WP_REST_Request('POST', '/bmltwf/v1/geoservice');

        $Intstub = \Mockery::mock('Integration');

        $rest = new GeoServiceHandler($Intstub);
        $response = $rest->post_geoservice_handler($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals(422, $response->get_error_data()['status']);
    }

    /**
     * @covers bmltwf\REST\Handlers\GeoServiceHandler::post_geoservice_handler
     */
    public function test_post_geoservice_invalid_key_fails(): void
    {
        $request = new WP_REST_Request('POST', '/bmltwf/v1/geoservice');
        $request['bmltwf_geo_service_key'] = 'geo_bad';

        $Intstub = \Mockery::mock('Integration');
        $Intstub->shouldReceive('geoServiceBaseUrl')->andReturn('https://geo.aws.bmlt.app');
        $Intstub->shouldReceive('testGeoService')->andReturn(401);

        $rest = new GeoServiceHandler($Intstub);
        $response = $rest->post_geoservice_handler($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals(422, $response->get_error_data()['status']);
    }

    /**
     * @covers bmltwf\REST\Handlers\GeoServiceHandler::patch_geoservice_handler
     */
    public function test_patch_geoservice_persists_and_encrypts(): void
    {
        $request = new WP_REST_Request('PATCH', '/bmltwf/v1/geoservice');
        $request['bmltwf_geo_service_key'] = 'geo_secretkey';

        $saved = array();
        Functions\when('\update_option')->alias(function ($name, $value) use (&$saved) {
            $saved[$name] = $value;
            return true;
        });

        $Intstub = \Mockery::mock('Integration');

        $rest = new GeoServiceHandler($Intstub);
        $response = $rest->patch_geoservice_handler($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        // test status marked success (URL is hardcoded, not saved)
        $this->assertEquals('success', $saved['bmltwf_geo_service_test_status']);

        // key is stored encrypted (an array with config + encrypted), never plaintext
        $this->assertIsArray($saved['bmltwf_geo_service_key']);
        $this->assertArrayHasKey('encrypted', $saved['bmltwf_geo_service_key']);
        $this->assertArrayHasKey('config', $saved['bmltwf_geo_service_key']);
        $this->assertNotEquals('geo_secretkey', $saved['bmltwf_geo_service_key']['encrypted']);
    }
}

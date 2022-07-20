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

use wbw\REST\Handlers\OptionsHandler;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};
require_once('config_phpunit.php');

/**
 * @covers wbw\REST\Handlers\OptionsHandler
 * @uses wbw\WBW_Debug
 * @uses wbw\REST\HandlerCore
 * @uses wbw\BMLT\Integration
 * @uses wbw\WBW_Database
 * @uses wbw\WBW_WP_Options
 */
final class OptionsHandlerTest extends TestCase
{
    use \wbw\WBW_Debug;

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
        if (!class_exists('wpdb')){
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
        unset($this->wbw_dbg);

    }

// test for POST options/backup (retrieve backup json files)
    /**
     * @covers wbw\REST\Handlers\OptionsHandler::post_wbw_backup_handler
     */
    public function test_can_post_options_backup_with_success(): void
    {

        
        $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/options/backup");
        $request->set_header('content-type', 'application/json');
        $request->set_route("/wbw/v1/options/backup");
        $request->set_method('POST');

        $dblookup = array(
            '0' => array(
                "service_body_bigint" => "2",
                "service_body_name" => "Sydney Metro",
                "show_on_form" => "1"
            ),
            '1' => array(
                "service_body_bigint" =>"3",
                "service_body_name" =>"Sydney North",
                "show_on_form" =>"1"
            ),
            '2' => array(
                "service_body_bigint" => "4",
                "service_body_name" =>"Sydney South",
                "show_on_form" =>"1"
            )
        );

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('prepare')->andReturn("SELECT * from anything");
        $wpdb->shouldReceive('get_results')->andReturn($dblookup);
        $wpdb->prefix = "";

        // Functions\when('\get_option')->justReturn("success");

        Functions\when('\wp_load_alloptions')->justReturn(array('wbw_db_version'=> 'testing', 'wbw_crap'=> 'testing', 'shouldntbe' => 'inthebackup'));
        $rest = new OptionsHandler();

        $response = $rest->post_wbw_backup_handler($request);

        $this->debug_log(($response));
        $data = $response->get_data();
        $this->assertEquals($data['message'],'Backup Successful');
        $backup = json_decode($data['backup'],true);
        $this->assertIsArray($backup['options']);
        $this->assertArrayNotHasKey('wbw_crap',$backup['options']);
        
        $this->assertEquals($backup['options']['wbw_db_version'],'testing');
        
    }


// // test for POST options/restore (retrieve backup json files)
//     /**
//      * @covers wbw\REST\Handlers\OptionsHandler::post_wbw_restore_handler
//      */
//     public function test_can_post_options_restore_with_success(): void
//     {

        
//         $request = new WP_REST_Request('POST', "http://54.153.167.239/flop/wp-json/wbw/v1/options/restore");
//         $request->set_header('content-type', 'application/json');
//         $request->set_route("/wbw/v1/options/restore");
//         $request->set_method('POST');

//         $dblookup = array(
//             '0' => array(
//                 "service_body_bigint" => "2",
//                 "service_body_name" => "Sydney Metro",
//                 "show_on_form" => "1"
//             ),
//             '1' => array(
//                 "service_body_bigint" =>"3",
//                 "service_body_name" =>"Sydney North",
//                 "show_on_form" =>"1"
//             ),
//             '2' => array(
//                 "service_body_bigint" => "4",
//                 "service_body_name" =>"Sydney South",
//                 "show_on_form" =>"1"
//             )
//         );

//         global $wpdb;
//         $wpdb =  Mockery::mock('wpdb');
//         /** @var Mockery::mock $wpdb test */
//         $wpdb->shouldReceive('prepare')->andReturn("SELECT * from anything");
//         $wpdb->shouldReceive('get_results')->andReturn($dblookup);

//         $WBW_WP_Options =  Mockery::mock('WBW_WP_Options');
//         /** @var Mockery::mock $WBW_WP_Options test */
//         Functions\when('\get_option')->justReturn("success");

//         Functions\when('\wp_load_alloptions')->justReturn(array('wbw_db_version'=> 'testing', 'wbw_crap'=> 'testing', 'shouldntbe' => 'inthebackup'));
//         $rest = new OptionsHandler();

//         $response = $rest->post_wbw_backup_handler($request);

//         $this->debug_log(($response));
//         $data = $response->get_data();
//         $this->assertEquals($data['message'],'Backup Successful');
//         $backup = json_decode($data['backup'],true);
//         $this->assertIsArray($backup['options']);
//         $this->assertArrayNotHasKey('wbw_crap',$backup['options']);
//         $this->assertEquals($backup['options']['wbw_db_version'],'testing');
        
//     }

}
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

use bmltwf\REST\Controller;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

class stub_Options {

    public function __construct()
    {
    // capability for managing submissions
        $this->bmltwf_capability_manage_submissions = 'bmltwf_manage_submissions';
    }
};

/**
 * @covers bmltwf\REST\Controller
 * @uses bmltwf\BMLTWF_Debug
 */
final class ControllerTest extends TestCase
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
        unset($this->bmltwf_dbg);
    }

    /**
     * @covers bmltwf\REST\Controller::delete_submission_permissions_check
     */
    public function test_can_call_delete_submission_permissions_check_as_admin(): void
    {
        Functions\when('get_option')->justReturn("false");
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn("1");

        $integration = new Controller(true);
        $response = $integration->delete_submission_permissions_check("{'hello':'hi'}");
        $this->assertNotInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(true, $response);
    }

        /**
     * @covers bmltwf\REST\Controller::delete_submission_permissions_check
     */
    public function test_can_call_delete_submission_permissions_check_as_submission_editor(): void
    {

        Functions\when('get_option')->justReturn("true");
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn("1");
        $stub = new stub_Options();
        $integration = new Controller($stub);
        $response = $integration->delete_submission_permissions_check("{'hello':'hi'}");
        $this->assertNotInstanceOf(\WP_Error::class, $response);
        $this->assertEquals(true, $response);
    }
        /**
     * @covers bmltwf\REST\Controller::delete_submission_permissions_check
     */
    public function test_cant_call_delete_submission_permissions_check_as_unpriv(): void
    {
        Functions\when('get_option')->justReturn("true");
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn("1");
        Functions\when('esc_html__')->justReturn("hello");
        Functions\when('is_user_logged_in')->justReturn(true);
        $stub = new stub_Options();
        $integration = new Controller($stub);
        $response = $integration->delete_submission_permissions_check("{'hello':'hi'}");
        $this->assertInstanceOf(\WP_Error::class, $response);
    }
}
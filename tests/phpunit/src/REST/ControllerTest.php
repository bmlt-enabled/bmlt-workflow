<?php

declare(strict_types=1);

use bmltwf\REST\Controller;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;

require_once('config_phpunit.php');

/**
 * Controller Tests
 * @covers bmltwf\REST\Controller
 */
final class ControllerTest extends TestCase
{
    use \bmltwf\BMLTWF_Debug;

    protected function setUp(): void
    {
        parent::setUp();
        Brain\Monkey\setUp();
        
        $basedir = getcwd();
        require_once($basedir . '/vendor/autoload.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-request.php');
        
        Functions\when('__')->returnArg();
        Functions\when('\current_user_can')->justReturn(false);
        Functions\when('\get_option')->justReturn('test');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers bmltwf\REST\Controller::__construct
     */
    public function test_controller_instantiation(): void
    {
        $controller = new Controller();
        $this->assertInstanceOf(Controller::class, $controller);
    }
}
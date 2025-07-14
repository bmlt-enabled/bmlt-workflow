<?php

declare(strict_types=1);

use bmltwf\REST\Handlers\OptionsHandler;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;

require_once('config_phpunit.php');

/**
 * OptionsHandler Tests
 * @covers bmltwf\REST\Handlers\OptionsHandler
 */
final class OptionsHandlerTest extends TestCase
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
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-response.php');
        
        Functions\when('__')->returnArg();
        Functions\when('\get_option')->justReturn('test');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_restore_handler
     */
    public function test_restore_missing_options_section(): void
    {
        $handler = new OptionsHandler();
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')->andReturn([
            'submissions' => [],
            'service_bodies' => []
            // Missing 'options' section
        ]);
        
        $result = $handler->post_bmltwf_restore_handler($request);
        
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_backup_handler
     */
    public function test_backup_data_integrity(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('get_results')->times(3)->andReturn([
            (object)['id' => 1, 'name' => 'Test']
        ]);
        
        Functions\when('\wp_load_alloptions')->justReturn([
            'bmltwf_db_version' => '1.1.18',
            'other_option' => 'value'
        ]);
        
        $handler = new OptionsHandler();
        
        $request = Mockery::mock('WP_REST_Request');
        $result = $handler->post_bmltwf_backup_handler($request);
        
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        
        $data = $result->get_data();
        $backup = json_decode($data['backup'], true);
        
        $this->assertArrayHasKey('options', $backup);
        $this->assertArrayHasKey('submissions', $backup);
        $this->assertArrayHasKey('service_bodies', $backup);
        $this->assertArrayHasKey('service_bodies_access', $backup);
    }


}
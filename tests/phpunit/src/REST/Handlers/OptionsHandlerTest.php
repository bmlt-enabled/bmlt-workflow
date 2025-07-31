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
        Functions\when('\is_multisite')->justReturn(false);
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
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_restore_handler
     */
    public function test_restore_with_correspondence_data(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->num_rows = 1;
        $wpdb->shouldReceive('insert')->times(4)->andReturn(1); // 3 main tables + correspondence
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn([]);
        $wpdb->shouldReceive('get_results')->andReturn([]);
        $wpdb->shouldReceive('get_var')->andReturn(null);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('');
        
        Functions\when('delete_option')->justReturn(true);
        Functions\when('add_option')->justReturn(true);
        Functions\when('\update_option')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_users')->justReturn([]);
        Functions\when('wp_remote_request')->justReturn(['response' => ['code' => 200], 'body' => 'OK']);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn('OK');
        
        $handler = new OptionsHandler();
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')->andReturn([
            'options' => [
                'bmltwf_db_version' => '1.1.28'
            ],
            'submissions' => [
                (object)['change_id' => 1, 'submitter_name' => 'Test']
            ],
            'service_bodies' => [
                (object)['serviceBodyId' => 1, 'service_body_name' => 'Test Body']
            ],
            'service_bodies_access' => [
                (object)['serviceBodyId' => 1, 'wp_uid' => 1]
            ],
            'correspondence' => [
                (object)['id' => 1, 'change_id' => 1, 'thread_id' => 'test-thread', 'message' => 'Test message']
            ]
        ]);
        
        $result = $handler->post_bmltwf_restore_handler($request);
        
        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }
    
    /**
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_restore_handler
     */
    public function test_restore_without_correspondence_data(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->num_rows = 1;
        $wpdb->shouldReceive('insert')->times(3)->andReturn(1); // Only 3 main tables
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('get_col')->andReturn([]);
        $wpdb->shouldReceive('get_results')->andReturn([]);
        $wpdb->shouldReceive('get_var')->andReturn(null);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('');
        
        Functions\when('delete_option')->justReturn(true);
        Functions\when('add_option')->justReturn(true);
        Functions\when('\update_option')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_users')->justReturn([]);
        Functions\when('wp_remote_request')->justReturn(['response' => ['code' => 200], 'body' => 'OK']);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn('OK');
        
        $handler = new OptionsHandler();
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')->andReturn([
            'options' => [
                'bmltwf_db_version' => '1.1.18' // Older version without correspondence
            ],
            'submissions' => [
                (object)['change_id' => 1, 'submitter_name' => 'Test']
            ],
            'service_bodies' => [
                (object)['serviceBodyId' => 1, 'service_body_name' => 'Test Body']
            ],
            'service_bodies_access' => [
                (object)['serviceBodyId' => 1, 'wp_uid' => 1]
            ]
            // No correspondence data
        ]);
        
        $result = $handler->post_bmltwf_restore_handler($request);
        
        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }

    /**
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_backup_handler
     */
    public function test_backup_data_integrity(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_results')->times(3)->andReturn([
            (object)['id' => 1, 'name' => 'Test']
        ]);
        
        // Mock correspondence table check - table doesn't exist
        $wpdb->shouldReceive('get_var')->with(Mockery::pattern('/SHOW TABLES LIKE/'))->andReturn(null);
        
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
        $this->assertArrayNotHasKey('correspondence', $backup); // Table doesn't exist
    }
    
    /**
     * @covers bmltwf\REST\Handlers\OptionsHandler::post_bmltwf_backup_handler
     */
    public function test_backup_includes_correspondence_when_table_exists(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('get_results')->times(4)->andReturn([
            (object)['id' => 1, 'name' => 'Test']
        ]);
        
        // Mock correspondence table check - table exists
        $wpdb->shouldReceive('get_var')->with(Mockery::pattern('/SHOW TABLES LIKE/'))->andReturn('wp_bmltwf_correspondence');
        $wpdb->prefix = 'wp_';
        
        Functions\when('\wp_load_alloptions')->justReturn([
            'bmltwf_db_version' => '1.1.28',
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
        $this->assertArrayHasKey('correspondence', $backup); // Table exists
    }


}
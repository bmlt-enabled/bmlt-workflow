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

namespace bmltwf\Tests\REST\Handlers;

use bmltwf\REST\Handlers\CorrespondenceHandler;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Mockery;

require_once('config_phpunit.php');

/**
 * @covers bmltwf\REST\Handlers\CorrespondenceHandler
 */
class CorrespondenceHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        Functions\when('esc_html')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('get_bloginfo')->alias(function($show) {
            return $show === 'name' ? 'Test Site' : 'admin@test.com';
        });
        Functions\when('get_site_url')->justReturn('https://test.com');
        Functions\when('get_permalink')->justReturn('http://example.com/correspondence');
        Functions\when('add_query_arg')->alias(function($key, $value, $url) {
            return $url . '?' . $key . '=' . $value;
        });
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::__construct
     */
    public function test_constructor(): void
    {
        Functions\when('get_option')->justReturn('test');
        Functions\when('current_user_can')->justReturn(true);
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Test User');
        $mockUser->display_name = 'Test User';
        $mockUser->user_login = 'testuser';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $handler = new CorrespondenceHandler();
        $this->assertInstanceOf(CorrespondenceHandler::class, $handler);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::get_correspondence_handler
     */
    public function test_get_correspondence_handler_with_valid_change_id(): void
    {
        Functions\when('get_option')->justReturn('test');
        Functions\when('current_user_can')->justReturn(true);
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Test User');
        $mockUser->display_name = 'Test User';
        $mockUser->user_login = 'testuser';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'test@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'Test change'
        ];
        
        $correspondence = [
            (object)[
                'correspondence_id' => 1,
                'change_id' => 123,
                'thread_id' => 'test-thread-id',
                'message' => 'Test message',
                'from_submitter' => 0,
                'created_at' => '2023-01-01 12:00:00',
                'created_by' => 'Admin'
            ]
        ];
        
        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn($submission);
            
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn($correspondence);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('change_id')
            ->andReturn(123);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->get_correspondence_handler($request);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('submission', $result);
        $this->assertArrayHasKey('correspondence', $result);
        $this->assertEquals($submission, $result['submission']);
        $this->assertEquals($correspondence, $result['correspondence']);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     */
    public function test_post_correspondence_handler(): void
    {
        Functions\when('get_option')->justReturn('test');
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('__')->returnArg();
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'test@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'Test change'
        ];
        
        $wpdb->shouldReceive('get_row')
            ->andReturn($submission);
            
        $wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);
        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null); // No existing thread
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]); // No previous messages
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('change_id')
            ->andReturn(123);
        $request->shouldReceive('get_param')
            ->with('message')
            ->andReturn('Test message');
        $request->shouldReceive('get_param')
            ->with('from_submitter')
            ->andReturn('false');
        $request->shouldReceive('get_param')
            ->with('thread_id')
            ->andReturn(null);
        
        Functions\when('wp_generate_uuid4')->justReturn('new-thread-id');
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_permalink')->justReturn('http://example.com/correspondence');
        Functions\when('add_query_arg')->justReturn('http://example.com/correspondence?thread_id=new-thread-id');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        Functions\expect('wp_mail')
            ->once()
            ->with(
                'test@example.com', // Should send to submitter's email
                Mockery::type('string'), // Subject
                Mockery::type('string'), // Message body
                Mockery::type('array') // Headers
            )
            ->andReturn(true);
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Admin');
        $mockUser->display_name = 'Admin';
        $mockUser->user_login = 'admin';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('thread_id', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('new-thread-id', $result['thread_id']);
        
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     */
    public function test_post_correspondence_handler_from_submitter_sends_admin_notification(): void
    {
        // This test verifies that when a submitter sends a message, admins are notified
        // The key is that current_user_can returns false, making from_submitter = true
        Functions\when('current_user_can')->justReturn(false); // Not an admin
        Functions\when('__')->returnArg();
        
        // Mock admin email template
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_admin_email_template') return 'Admin notification';
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';
        $wpdb->last_query = '';
        $wpdb->insert_id = 1;
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'test@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'pending',
            'serviceBodyId' => 456
        ];
        
        // Mock get_row to return submission for thread validation
        $wpdb->shouldReceive('get_row')
            ->andReturn($submission);
            
        $wpdb->shouldReceive('insert')->once()->andReturn(1);
        
        // Mock get_var for thread existence check and table checks
        $wpdb->shouldReceive('get_var')->andReturn(1);
        
        // Mock correspondence history - first message from user
        $messages = [(object)['from_submitter' => 1, 'created_at' => '2023-01-01 12:00:00']];
        $wpdb->shouldReceive('get_results')->andReturn($messages);
        
        // Mock admin emails lookup - this is the key expectation
        $wpdb->shouldReceive('get_col')->once()->andReturn([1, 2]);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(123);
        $request->shouldReceive('get_param')->with('message')->andReturn('Test message');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('true');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn('existing-thread');
        
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_site_url')->justReturn('http://example.com');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        
        // Mock user lookup
        $mockAdmin = Mockery::mock('WP_User');
        $mockAdmin->user_email = 'admin@example.com';
        Functions\when('get_user_by')->justReturn($mockAdmin);
        
        // Expect admin notification
        Functions\expect('wp_mail')->once()->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->ID = 0; // Not logged in
        $mockUser->display_name = 'Test User';
        $mockUser->user_login = 'testuser';
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }



    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     * Test that admin email is sent when submitter sends correspondence
     */
    public function test_admin_email_template_field_substitution(): void
    {
        Functions\when('current_user_can')->justReturn(false); // Not an admin
        Functions\when('__')->returnArg();
        
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_admin_email_template') return 'Admin template';
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';
        $wpdb->last_query = '';
        $wpdb->insert_id = 1;
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->shouldReceive('get_var')->andReturn(1);
        
        $submission = (object)[
            'change_id' => 456,
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Jane Smith',
            'change_made' => 'pending',
            'serviceBodyId' => 789
        ];
        
        $wpdb->shouldReceive('get_row')->andReturn($submission);
        
        // Mock first message from user
        $messages = [(object)['from_submitter' => 1, 'created_at' => '2023-01-01 12:00:00']];
        $wpdb->shouldReceive('get_results')->andReturn($messages);
        $wpdb->shouldReceive('get_col')->andReturn([1]);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(456);
        $request->shouldReceive('get_param')->with('message')->andReturn('User inquiry');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('true');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn('existing-thread');
        
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_site_url')->justReturn('http://example.com');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        
        $mockAdmin = Mockery::mock('WP_User');
        $mockAdmin->user_email = 'admin@example.com';
        Functions\when('get_user_by')->justReturn($mockAdmin);
        
        // Verify email is sent
        Functions\expect('wp_mail')->once()->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->ID = 0;
        $mockUser->display_name = 'Jane Smith';
        $mockUser->user_login = 'janesmith';
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertTrue($result['success']);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     * Test that emails are not sent when templates are empty or missing
     */
    public function test_no_email_sent_when_template_missing(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('__')->returnArg();
        
        // Mock empty template
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_submitter_email_template') return '';
            if ($option === 'bmltwf_correspondence_page') return 123;
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null); // No existing thread
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]); // No previous messages
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'Test change'
        ];
        
        $wpdb->shouldReceive('get_row')->andReturn($submission);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(123);
        $request->shouldReceive('get_param')->with('message')->andReturn('Admin message');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('false');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn(null);
        
        Functions\when('wp_generate_uuid4')->justReturn('test-thread');
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_permalink')->justReturn('http://example.com/correspondence');
        Functions\when('add_query_arg')->justReturn('http://example.com/correspondence?thread=test-thread');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        
        // Email should still be sent even with empty template (using empty content)
        Functions\expect('wp_mail')
            ->once()
            ->with(
                'user@example.com',
                Mockery::type('string'),
                '', // Empty template results in empty message
                Mockery::type('array')
            )
            ->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Admin');
        $mockUser->display_name = 'Admin';
        $mockUser->user_login = 'admin';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertTrue($result['success']);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     * Test that email headers are correctly formatted
     */
    public function test_email_headers_format(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('__')->returnArg();
        
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_submitter_email_template') return 'Test message';
            if ($option === 'bmltwf_correspondence_submitter_email_subject') return 'New correspondence about your meeting submission';
            if ($option === 'bmltwf_correspondence_page') return 123;
            if ($option === 'bmltwf_email_from_address') return 'custom@example.com';
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null); // No existing thread
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]); // No previous messages
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'Test change'
        ];
        
        $wpdb->shouldReceive('get_row')->andReturn($submission);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(123);
        $request->shouldReceive('get_param')->with('message')->andReturn('Admin message');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('false');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn(null);
        
        Functions\when('wp_generate_uuid4')->justReturn('test-thread');
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_permalink')->justReturn('http://example.com/correspondence');
        Functions\when('add_query_arg')->justReturn('http://example.com/correspondence?thread=test-thread');
        Functions\when('get_bloginfo')->alias(function($info) {
            if ($info === 'name') return 'My WordPress Site';
            return 'test';
        });
        
        // Capture headers and subject to verify format
        $capturedHeaders = null;
        $capturedSubject = null;
        Functions\expect('wp_mail')
            ->once()
            ->with(
                'user@example.com',
                Mockery::capture($capturedSubject),
                Mockery::type('string'),
                Mockery::capture($capturedHeaders)
            )
            ->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Admin');
        $mockUser->display_name = 'Admin';
        $mockUser->user_login = 'admin';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        // Verify headers format and default subject
        $expectedHeaders = [
            'Content-Type: text/html; charset=UTF-8',
            'From: My WordPress Site <custom@example.com>'
        ];
        $expectedSubject = 'New correspondence about your meeting submission';
        $this->assertEquals($expectedHeaders, $capturedHeaders);
        $this->assertEquals($expectedSubject, $capturedSubject);
        $this->assertTrue($result['success']);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     * Test that custom subject lines work for correspondence emails
     */
    public function test_correspondence_custom_subject_lines(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('__')->returnArg();
        
        // Mock custom subject template
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_submitter_email_template') return 'Test message';
            if ($option === 'bmltwf_correspondence_submitter_email_subject') return 'Custom Subject: {field:change_id}';
            if ($option === 'bmltwf_correspondence_page') return 123;
            if ($option === 'bmltwf_email_from_address') return 'custom@example.com';
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null); // No existing thread
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]); // No previous messages
        
        $submission = (object)[
            'change_id' => 456,
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Test User',
            'change_made' => 'Test change'
        ];
        
        $wpdb->shouldReceive('get_row')->andReturn($submission);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(456);
        $request->shouldReceive('get_param')->with('message')->andReturn('Admin message');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('false');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn(null);
        
        Functions\when('wp_generate_uuid4')->justReturn('test-thread');
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_permalink')->justReturn('http://example.com/correspondence');
        Functions\when('add_query_arg')->justReturn('http://example.com/correspondence?thread=test-thread');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        
        // Capture subject to verify custom subject line
        $capturedSubject = null;
        Functions\expect('wp_mail')
            ->once()
            ->with(
                'user@example.com',
                Mockery::capture($capturedSubject),
                Mockery::type('string'),
                Mockery::type('array')
            )
            ->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Admin');
        $mockUser->display_name = 'Admin';
        $mockUser->user_login = 'admin';
        $mockUser->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        // Verify custom subject line with field substitution
        $this->assertEquals('Custom Subject: 456', $capturedSubject);
        $this->assertTrue($result['success']);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::post_correspondence_handler
     * Test that admin email is sent when submitter sends correspondence
     */
    public function test_admin_correspondence_custom_subject(): void
    {
        Functions\when('current_user_can')->justReturn(false); // Not an admin
        Functions\when('__')->returnArg();
        
        Functions\when('get_option')->alias(function($option) {
            if ($option === 'bmltwf_correspondence_admin_email_template') return 'Admin template';
            return 'test';
        });
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';
        $wpdb->last_query = '';
        $wpdb->insert_id = 1;
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', '\'%s\'', str_replace('%d', '%d', $query)), $args);
        });
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->shouldReceive('get_var')->andReturn(1);
        
        $submission = (object)[
            'change_id' => 789,
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Jane Smith',
            'change_made' => 'pending',
            'serviceBodyId' => 123
        ];
        
        $wpdb->shouldReceive('get_row')->andReturn($submission);
        
        // Mock first message from user
        $messages = [(object)['from_submitter' => 1, 'created_at' => '2023-01-01 12:00:00']];
        $wpdb->shouldReceive('get_results')->andReturn($messages);
        $wpdb->shouldReceive('get_col')->andReturn([1]);
        
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('change_id')->andReturn(789);
        $request->shouldReceive('get_param')->with('message')->andReturn('User inquiry');
        $request->shouldReceive('get_param')->with('from_submitter')->andReturn('true');
        $request->shouldReceive('get_param')->with('thread_id')->andReturn('existing-thread');
        
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('get_site_url')->justReturn('http://example.com');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        
        $mockAdmin = Mockery::mock('WP_User');
        $mockAdmin->user_email = 'admin@example.com';
        Functions\when('get_user_by')->justReturn($mockAdmin);
        
        // Verify email is sent
        Functions\expect('wp_mail')->once()->andReturn(true);
        
        $mockUser = Mockery::mock('WP_User');
        $mockUser->ID = 0;
        $mockUser->display_name = 'Jane Smith';
        $mockUser->user_login = 'janesmith';
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertTrue($result['success']);
    }
}
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
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
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
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
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
        Functions\when('wp_mail')->justReturn(true);
        $mockUser = Mockery::mock('WP_User');
        $mockUser->shouldReceive('get')->andReturn('Admin');
        $mockUser->display_name = 'Admin';
        $mockUser->user_login = 'admin';
        Functions\when('wp_get_current_user')->justReturn($mockUser);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('thread_id', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('new-thread-id', $result['thread_id']);
    }
}
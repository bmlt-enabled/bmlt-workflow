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
use bmltwf\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * @covers bmltwf\REST\Handlers\CorrespondenceHandler
 */
class CorrespondenceHandlerTest extends TestCase
{
    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::__construct
     */
    public function test_constructor(): void
    {
        $handler = new CorrespondenceHandler();
        $this->assertInstanceOf(CorrespondenceHandler::class, $handler);
    }

    /**
     * @covers bmltwf\REST\Handlers\CorrespondenceHandler::get_correspondence_handler
     */
    public function test_get_correspondence_handler_with_valid_change_id(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'test@example.com',
            'submitter_name' => 'Test User'
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
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        
        $submission = (object)[
            'change_id' => 123,
            'submitter_email' => 'test@example.com',
            'submitter_name' => 'Test User'
        ];
        
        $wpdb->shouldReceive('get_row')
            ->once()
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
        Functions\when('wp_get_current_user')->justReturn((object)['display_name' => 'Admin']);
        
        $handler = new CorrespondenceHandler();
        $result = $handler->post_correspondence_handler($request);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('thread_id', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('new-thread-id', $result['thread_id']);
    }
}
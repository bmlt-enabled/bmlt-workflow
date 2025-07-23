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

namespace bmltwf\Tests;

use bmltwf\BMLTWF_Database;
use Brain\Monkey\Functions;
use Mockery;

/**
 * @covers bmltwf\BMLTWF_Database
 */
class BMLTWF_CorrespondenceTest extends TestCase
{
    /**
     * @covers bmltwf\BMLTWF_Database::createCorrespondenceTable
     */
    public function test_createCorrespondenceTable(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('query')->atLeast()->once();
        $wpdb->prefix = "";

        $database = new BMLTWF_Database();
        $database->createCorrespondenceTable('utf8mb4_unicode_ci');
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_adds_correspondence_table(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->num_rows = 1; // Mock table exists check
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('utf8mb4_unicode_ci');
        
        Functions\when('\update_option')->justReturn(true);
        Functions\when('\delete_option')->justReturn(true);
        Functions\when('\add_option')->justReturn(true);
        Functions\when('\get_option')->alias(function($option) {
            return $option === 'bmltwf_db_version' ? '1.1.24' : false;
        });
        
        $database = new BMLTWF_Database();
        $result = $database->bmltwf_db_upgrade('1.1.25', false);
        
        $this->assertEquals(2, $result, 'Database upgrade should return 2 for successful upgrade');
    }
}
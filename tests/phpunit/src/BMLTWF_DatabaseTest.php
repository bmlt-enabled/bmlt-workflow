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

use bmltwf\BMLTWF_Database;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

/**
 * @covers bmltwf\BMLTWF_Database
 * @uses bmltwf\BMLTWF_Debug
 */
final class BMLTWF_DatabaseTest extends TestCase
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
        parent::setUp();
        $this->setVerboseErrorHandler();
        $basedir = getcwd();
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/autoload.php');
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/wp/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/wp/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/wp/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();
        
        Functions\when('\is_multisite')->justReturn(false);

    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_from_current_without_fresh_install_does_nothing(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->num_rows = 1;
        $wpdb->prefix = "";
        
        // Mock correspondence table check (now always runs)
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'bmltwf_correspondence'")
            ->andReturn('bmltwf_correspondence'); // Table exists

        $BMLTWF_Database = new BMLTWF_Database();
        
        Functions\when('\get_option')->justReturn($BMLTWF_Database->bmltwf_db_version);
        // nothing should be performed ie return 0
        $this->assertEquals($BMLTWF_Database->bmltwf_db_upgrade($BMLTWF_Database->bmltwf_db_version, false), 0);
        
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_missing_tables_does_fresh_install(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed


        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 0;
        $wpdb->prefix = "";

        $BMLTWF_Database = new BMLTWF_Database();

        Functions\expect('\delete_option')->with('bmltwf_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('bmltwf_db_version',$BMLTWF_Database->bmltwf_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn($BMLTWF_Database->bmltwf_db_version);
        // fresh install should be performed
        $this->assertEquals($BMLTWF_Database->bmltwf_db_upgrade($BMLTWF_Database->bmltwf_db_version, false), 1);
        
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_performs_upgrade_with_low_version(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        
        // More specific mocking for get_var calls
        $wpdb->shouldReceive('get_var')
            ->with(Mockery::on(function($query) {
                return strpos($query, 'SHOW TABLES LIKE') !== false;
            }))
            ->andReturn(1); // Tables exist
            
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'bmltwf_debug_log'")
            ->andReturn('bmltwf_debug_log'); // Debug log table exists
            
        $wpdb->shouldReceive('get_row')
            ->with("SHOW COLUMNS FROM bmltwf_debug_log LIKE 'log_time'")
            ->andReturn((object)['Type' => 'datetime']); // Without microsecond precision
            
        $wpdb->shouldReceive('get_results')->andReturn([]);
        $wpdb->num_rows = 1;
        $wpdb->prefix = "";

        $BMLTWF_Database = new BMLTWF_Database();

        Functions\expect('\delete_option')->with('bmltwf_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('bmltwf_db_version',$BMLTWF_Database->bmltwf_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn("0.0.1");
        // upgrade should be performed
        $this->assertEquals($BMLTWF_Database->bmltwf_db_upgrade($BMLTWF_Database->bmltwf_db_version, false), 2);
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_no_version_does_fresh_install(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 1;
        $wpdb->prefix = "";

        $BMLTWF_Database = new BMLTWF_Database();

        Functions\expect('\delete_option')->once()->andReturn(true);
        Functions\expect('\add_option')->once()->andReturn(true);

        Functions\when('\get_option')->justReturn(false);
        // fresh install should be performed
        $this->assertEquals($BMLTWF_Database->bmltwf_db_upgrade($BMLTWF_Database->bmltwf_db_version, false), 1);
    }


    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_database_upgrade_fresh_install_requested_does_fresh_install(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed


        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 1;
        $wpdb->prefix = "";

        $BMLTWF_Database = new BMLTWF_Database();

        Functions\expect('\delete_option')->with('bmltwf_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('bmltwf_db_version',$BMLTWF_Database->bmltwf_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn(false);
        // fresh install should be performed
        $this->assertEquals($BMLTWF_Database->bmltwf_db_upgrade($BMLTWF_Database->bmltwf_db_version, true), 1);
    }

    /**
     * @covers bmltwf\BMLTWF_Database::createTables
     */
    public function test_createTables_with_old_version(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('query')->atLeast()->once();
        $wpdb->prefix = "";

        $database = new BMLTWF_Database();
        $database->createTables('utf8mb4_unicode_ci', '0.4.0');
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @covers bmltwf\BMLTWF_Database::createTables
     */
    public function test_createTables_with_current_version(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->shouldReceive('query')->atLeast()->once();
        $wpdb->prefix = "";

        $database = new BMLTWF_Database();
        $database->createTables('utf8mb4_unicode_ci', '1.1.18');
        
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @covers bmltwf\BMLTWF_Database::upgradeComplexJsonFields
     */
    public function test_upgradeComplexJsonFields_transforms_data(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        
        $testData = [
            (object)[
                'change_id' => 1,
                'changes_requested' => '{"weekday_tinyint":"2","format_shared_id_list":"1,2,3","original_weekday_tinyint":"3","original_format_shared_id_list":"4,5"}'
            ]
        ];
        
        $wpdb->shouldReceive('get_results')->andReturn($testData);
        $wpdb->shouldReceive('update')->once()->with(
            Mockery::any(),
            Mockery::on(function($data) {
                $json = json_decode($data['changes_requested'], true);
                return isset($json['day']) && $json['day'] === 1 && 
                       isset($json['formatIds']) && $json['formatIds'] === [1,2,3];
            }),
            ['change_id' => 1]
        );

        $database = new BMLTWF_Database();
        $reflection = new ReflectionClass($database);
        $method = $reflection->getMethod('upgradeComplexJsonFields');
        $method->setAccessible(true);
        $method->invoke($database);
        
        $this->assertTrue(true);
    }

    /**
     * @covers bmltwf\BMLTWF_Database::testUpgrade
     */
    public function test_database_upgrade_from_old_version(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->num_rows = 1; // Mock table exists check
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('utf8mb4_unicode_ci');
        $wpdb->shouldReceive('get_results')->andReturn([
            (object)['TABLE_NAME' => 'bmltwf_submissions', 'COLUMN_NAME' => 'serviceBodyId'],
            (object)['TABLE_NAME' => 'bmltwf_service_bodies_access', 'COLUMN_NAME' => 'serviceBodyId']
        ]);
        $wpdb->shouldReceive('get_col')->andReturn(['change_id', 'serviceBodyId', 'id']);
        
        // Mock each specific get_var call separately
        $wpdb->shouldReceive('get_var')
            ->with("SELECT COUNT(*) FROM bmltwf_submissions s \n             LEFT JOIN bmltwf_service_bodies sb ON s.serviceBodyId = sb.serviceBodyId \n             WHERE sb.serviceBodyId IS NULL")
            ->andReturn(0); // No orphaned records
            
        $wpdb->shouldReceive('get_var')
            ->with("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES \n             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bmltwf_submissions'")
            ->andReturn(20); // Auto increment value
            
        $wpdb->shouldReceive('get_var')
            ->with("SELECT MAX(change_id) FROM bmltwf_submissions")
            ->andReturn(10); // Max ID
            
        // Mock debug log table check
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'bmltwf_debug_log'")
            ->andReturn('bmltwf_debug_log'); // Table exists
            
        // Mock column check for debug log table
        $wpdb->shouldReceive('get_row')
            ->with("SHOW COLUMNS FROM bmltwf_debug_log LIKE 'log_time'")
            ->andReturn((object)['Type' => 'datetime']); // Without microsecond precision
            
        // Mock column check for serviceBodyId in service bodies table
        $wpdb->shouldReceive('get_var')
            ->with("SHOW COLUMNS FROM bmltwf_service_bodies LIKE 'serviceBodyId'")
            ->andReturn('serviceBodyId'); // Column exists
            
        // Mock correspondence table check for ensureCorrespondenceTableExists
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'bmltwf_correspondence'")
            ->andReturn('bmltwf_correspondence'); // Table exists
        
        Functions\when('\update_option')->justReturn(true);
        Functions\when('\delete_option')->justReturn(true);
        Functions\when('\add_option')->justReturn(true);
        Functions\when('\current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('\get_option')->alias(function($option) {
            return $option === 'bmltwf_db_version' ? '1.1.17' : false;
        });
        
        // Mock wpdb insert and related methods for testUpgrade
        $wpdb->shouldReceive('insert')->andReturn(true);
        $wpdb->insert_id = 123;
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%d', '%s', $query), $args);
        });
        $wpdb->shouldReceive('get_row')->andReturn((object)['changes_requested' => '{"venueType":2,"original_venueType":1,"name":"Test Meeting"}']);
        $wpdb->shouldReceive('delete')->andReturn(true);
        
        $database = new BMLTWF_Database();
        $test_results = $database->testUpgrade('1.1.17');
        
        $this->assertTrue($test_results['success'], 
            'Database upgrade test failed: ' . implode(', ', $test_results['errors']));
        
        $this->assertEmpty($test_results['errors'], 
            'Upgrade validation errors: ' . implode(', ', $test_results['errors']));
    }

    /**
     * @covers bmltwf\BMLTWF_Database::validateUpgrade
     */
    public function test_database_validation(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive('get_col')->andReturn(['change_id', 'serviceBodyId', 'id']);
        $wpdb->shouldReceive('get_results')->andReturn([
            (object)['TABLE_NAME' => 'bmltwf_submissions', 'COLUMN_NAME' => 'serviceBodyId'],
            (object)['TABLE_NAME' => 'bmltwf_service_bodies_access', 'COLUMN_NAME' => 'serviceBodyId']
        ]);
        
        // Mock each specific get_var call separately
        $wpdb->shouldReceive('get_var')
            ->with("SELECT COUNT(*) FROM bmltwf_submissions s \n             LEFT JOIN bmltwf_service_bodies sb ON s.serviceBodyId = sb.serviceBodyId \n             WHERE sb.serviceBodyId IS NULL")
            ->andReturn(0); // No orphaned records
            
        $wpdb->shouldReceive('get_var')
            ->with("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES \n             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bmltwf_submissions'")
            ->andReturn(20); // Auto increment value
            
        $wpdb->shouldReceive('get_var')
            ->with("SELECT MAX(change_id) FROM bmltwf_submissions")
            ->andReturn(10); // Max ID
            
        // Mock debug log table check
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'bmltwf_debug_log'")
            ->andReturn('bmltwf_debug_log'); // Table exists
            
        // Mock column check for debug log table
        $wpdb->shouldReceive('get_row')
            ->with("SHOW COLUMNS FROM bmltwf_debug_log LIKE 'log_time'")
            ->andReturn((object)['Type' => 'datetime']); // Without microsecond precision
        
        $database = new BMLTWF_Database();
        $validation = $database->validateUpgrade();
        
        foreach ($validation as $category => $errors) {
            $this->assertEmpty($errors, 
                "Validation errors in $category: " . implode(', ', $errors));
        }
    }

    /**
     * @covers bmltwf\BMLTWF_Database::validateUpgrade
     */
    public function test_table_structure_validation(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive('get_col')->andReturn(['change_id', 'serviceBodyId', 'id']);
        $wpdb->shouldReceive('get_results')->andReturn([]);
        $wpdb->shouldReceive('get_var')->andReturn(0);
        
        $database = new BMLTWF_Database();
        $validation = $database->validateUpgrade();
        
        $this->assertArrayHasKey('tables', $validation);
        $this->assertEmpty($validation['tables'], 
            'Table structure validation failed: ' . implode(', ', $validation['tables']));
    }

    /**
     * @covers bmltwf\BMLTWF_Database::validateUpgrade
     */
    public function test_foreign_key_validation(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "";
        $wpdb->shouldReceive('get_col')->andReturn(['change_id', 'serviceBodyId', 'id']);
        $wpdb->shouldReceive('get_results')->andReturn([
            (object)['TABLE_NAME' => $wpdb->prefix . 'bmltwf_submissions', 'COLUMN_NAME' => 'serviceBodyId'],
            (object)['TABLE_NAME' => $wpdb->prefix . 'bmltwf_service_bodies_access', 'COLUMN_NAME' => 'serviceBodyId']
        ]);
        $wpdb->shouldReceive('get_var')->andReturn(0);
        
        $database = new BMLTWF_Database();
        $validation = $database->validateUpgrade();
        
        $this->assertArrayHasKey('foreign_keys', $validation);
        $this->assertEmpty($validation['foreign_keys'], 
            'Foreign key validation failed: ' . implode(', ', $validation['foreign_keys']));
    }

    /**
     * @covers bmltwf\BMLTWF_Database::createTables
     */
    public function test_debug_table_creation(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        
        // Capture the SQL query for creating the debug table
        $capturedQueries = [];
        $wpdb->shouldReceive('query')->andReturnUsing(function($query) use (&$capturedQueries) {
            $capturedQueries[] = $query;
            return true;
        });
        
        // Mock the debug log table check
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_debug_log'")
            ->andReturn(null); // Table doesn't exist yet
        
        $database = new BMLTWF_Database();
        $database->createTables('utf8mb4_unicode_ci', '1.1.18');
        
        // Check if any query contains the debug table creation
        $debugTableCreated = false;
        foreach ($capturedQueries as $query) {
            if (strpos($query, 'CREATE TABLE IF NOT EXISTS test_bmltwf_debug_log') !== false) {
                $debugTableCreated = true;
                
                // Verify the table has the expected columns
                $this->assertStringContainsString('log_id', $query);
                $this->assertStringContainsString('log_time', $query);
                $this->assertStringContainsString('log_caller', $query);
                $this->assertStringContainsString('log_message', $query);
                break;
            }
        }
        
        $this->assertTrue($debugTableCreated, 'Debug table creation query not found');
    }

    /**
     * @covers bmltwf\BMLTWF_Database::createTables
     */
    public function test_correspondence_table_creation_with_foreign_key(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        
        $capturedQueries = [];
        $wpdb->shouldReceive('query')->andReturnUsing(function($query) use (&$capturedQueries) {
            $capturedQueries[] = $query;
            return true;
        });
        
        $database = new BMLTWF_Database();
        $database->createTables('utf8mb4_unicode_ci', '1.1.28');
        
        // Verify correspondence table is created after submissions table
        $submissionsTableIndex = -1;
        $correspondenceTableIndex = -1;
        
        foreach ($capturedQueries as $index => $query) {
            if (strpos($query, 'CREATE TABLE test_bmltwf_submissions') !== false) {
                $submissionsTableIndex = $index;
            }
            if (strpos($query, 'CREATE TABLE IF NOT EXISTS test_bmltwf_correspondence') !== false) {
                $correspondenceTableIndex = $index;
                
                // Verify the table has foreign key constraint
                $this->assertStringContainsString('FOREIGN KEY (change_id) REFERENCES', $query);
                $this->assertStringContainsString('correspondence_id', $query);
                $this->assertStringContainsString('thread_id', $query);
                $this->assertStringContainsString('message', $query);
                $this->assertStringContainsString('from_submitter', $query);
            }
        }
        
        $this->assertGreaterThan(-1, $submissionsTableIndex, 'Submissions table creation not found');
        $this->assertGreaterThan(-1, $correspondenceTableIndex, 'Correspondence table creation not found');
        $this->assertGreaterThan($submissionsTableIndex, $correspondenceTableIndex, 
            'Correspondence table should be created after submissions table');
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     */
    public function test_missing_correspondence_table_created_during_upgrade(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        $wpdb->num_rows = 3; // All main tables exist
        
        $capturedQueries = [];
        $wpdb->shouldReceive('query')->andReturnUsing(function($query) use (&$capturedQueries) {
            $capturedQueries[] = $query;
            return true;
        });
        
        $wpdb->shouldReceive('get_charset_collate')->andReturn('utf8mb4_unicode_ci');
        
        // Mock correspondence table doesn't exist, but submissions table does
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_correspondence'")
            ->andReturn(null); // Table doesn't exist
            
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_submissions'")
            ->andReturn('test_bmltwf_submissions'); // Submissions table exists
        
        Functions\when('\get_option')->justReturn('1.1.27'); // Version that triggers upgrade
        Functions\when('\delete_option')->justReturn(true);
        Functions\when('\add_option')->justReturn(true);
        Functions\when('\is_multisite')->justReturn(false);
        
        $database = new BMLTWF_Database();
        $result = $database->bmltwf_db_upgrade('1.1.28', false);
        
        // Should perform upgrade (return 2)
        $this->assertEquals(2, $result, 'Expected upgrade to be performed');
        
        // Verify correspondence table creation was attempted
        $correspondenceTableCreated = false;
        $foreignKeyAdded = false;
        
        foreach ($capturedQueries as $query) {
            if (strpos($query, 'CREATE TABLE IF NOT EXISTS test_bmltwf_correspondence') !== false) {
                $correspondenceTableCreated = true;
            }
            if (strpos($query, 'ADD CONSTRAINT fk_correspondence_change_id') !== false) {
                $foreignKeyAdded = true;
            }
        }
        
        $this->assertTrue($correspondenceTableCreated, 'Correspondence table creation not found in queries: ' . implode('; ', $capturedQueries));
        $this->assertTrue($foreignKeyAdded, 'Foreign key constraint addition not found in queries: ' . implode('; ', $capturedQueries));
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     * Scenario B: Existing Installation with DB 1.1.28 (Working) - correspondence table exists
     */
    public function test_existing_installation_with_correspondence_table_working(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        $wpdb->num_rows = 3; // All main tables exist
        
        // Mock query calls for checkTablesExist()
        $wpdb->shouldReceive('query')->andReturn(true);
        
        // Mock correspondence table exists
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_correspondence'")
            ->andReturn('test_bmltwf_correspondence'); // Table exists
        
        Functions\when('\get_option')->justReturn('1.1.28'); // Current DB version
        
        $database = new BMLTWF_Database();
        $result = $database->bmltwf_db_upgrade('1.1.28', false);
        
        // Should return 0 (no upgrade needed)
        $this->assertEquals(0, $result, 'Expected no upgrade to be performed');
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     * Scenario C: Existing Installation with DB 1.1.28 (Broken) - correspondence table missing
     */
    public function test_existing_installation_missing_correspondence_table_fixed(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        $wpdb->num_rows = 3; // All main tables exist
        
        $capturedQueries = [];
        $wpdb->shouldReceive('query')->andReturnUsing(function($query) use (&$capturedQueries) {
            $capturedQueries[] = $query;
            return true;
        });
        
        $wpdb->shouldReceive('get_charset_collate')->andReturn('utf8mb4_unicode_ci');
        
        // Mock correspondence table doesn't exist, but submissions table does
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_correspondence'")
            ->andReturn(null); // Table doesn't exist
            
        $wpdb->shouldReceive('get_var')
            ->with("SHOW TABLES LIKE 'test_bmltwf_submissions'")
            ->andReturn('test_bmltwf_submissions'); // Submissions table exists
        
        Functions\when('\get_option')->justReturn('1.1.28'); // Current DB version (no schema upgrade needed)
        
        $database = new BMLTWF_Database();
        $result = $database->bmltwf_db_upgrade('1.1.28', false);
        
        // Should return 0 (no schema upgrade, but table fixed)
        $this->assertEquals(0, $result, 'Expected no schema upgrade but table should be fixed');
        
        // Verify correspondence table creation was attempted
        $correspondenceTableCreated = false;
        $foreignKeyAdded = false;
        
        foreach ($capturedQueries as $query) {
            if (strpos($query, 'CREATE TABLE IF NOT EXISTS test_bmltwf_correspondence') !== false) {
                $correspondenceTableCreated = true;
            }
            if (strpos($query, 'ADD CONSTRAINT fk_correspondence_change_id') !== false) {
                $foreignKeyAdded = true;
            }
        }
        
        $this->assertTrue($correspondenceTableCreated, 'Correspondence table creation should have been attempted');
        $this->assertTrue($foreignKeyAdded, 'Foreign key constraint should have been added');
    }

    /**
     * @covers bmltwf\BMLTWF_Database::bmltwf_db_upgrade
     * Scenario A: Fresh Installation
     */
    public function test_fresh_installation_creates_all_tables(): void
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = "test_";
        $wpdb->num_rows = 0; // No tables exist
        
        $capturedQueries = [];
        $wpdb->shouldReceive('query')->andReturnUsing(function($query) use (&$capturedQueries) {
            $capturedQueries[] = $query;
            return true;
        });
        
        $wpdb->shouldReceive('get_charset_collate')->andReturn('utf8mb4_unicode_ci');
        
        Functions\when('\get_option')->justReturn(false); // No DB version exists
        Functions\when('\delete_option')->justReturn(true);
        Functions\when('\add_option')->justReturn(true);
        
        $database = new BMLTWF_Database();
        $result = $database->bmltwf_db_upgrade('1.1.28', false);
        
        // Should return 1 (fresh install performed)
        $this->assertEquals(1, $result, 'Expected fresh install to be performed');
        
        // Verify correspondence table with FK was created
        $correspondenceTableWithFKCreated = false;
        
        foreach ($capturedQueries as $query) {
            if (strpos($query, 'CREATE TABLE IF NOT EXISTS test_bmltwf_correspondence') !== false &&
                strpos($query, 'FOREIGN KEY (change_id) REFERENCES') !== false) {
                $correspondenceTableWithFKCreated = true;
                break;
            }
        }
        
        $this->assertTrue($correspondenceTableWithFKCreated, 'Correspondence table with FK should be created during fresh install');
    }
}
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
        $wpdb->num_rows = 1;
        $wpdb->prefix = "";

        $BMLTWF_Database = new BMLTWF_Database();
        $wpdb->shouldReceive('get_results')->andReturn([]);

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

}
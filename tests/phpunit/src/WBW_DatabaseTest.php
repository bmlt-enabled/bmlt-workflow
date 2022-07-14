<?php

declare(strict_types=1);

use wbw\WBW_Database;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use function Patchwork\{redefine, getFunction, always};

require_once('config_phpunit.php');

/**
 * @covers wbw\WBW_Database
 * @uses wbw\WBW_Debug
 */
final class WBW_DatabaseTest extends TestCase
{
    use \wbw\WBW_Debug;

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
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-error.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/class-wp-http-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-response.php');
        require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/rest-api/class-wp-rest-request.php');
        if (!class_exists('wpdb')) {
            require_once($basedir . '/vendor/cyruscollier/wordpress-develop/src/wp-includes/wp-db.php');
        }

        Brain\Monkey\setUp();

        Functions\when('\unserialize')->returnArg();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
        parent::tearDown();
        Mockery::close();
        unset($this->wbw_dbg);
    }

    /**
     * @covers wbw\WBW_Database\wbw_db_upgrade
     */
    public function test_database_upgrade_from_current_without_fresh_install_does_nothing(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->num_rows = 1;

        $WBW_Database = new WBW_Database();
        Functions\when('\get_option')->justReturn($WBW_Database->wbw_db_version);
        // nothing should be performed ie return 0
        $this->assertEquals($WBW_Database->wbw_db_upgrade($WBW_Database->wbw_db_version, false), 0);
        
    }

    /**
     * @covers wbw\WBW_Database\wbw_db_upgrade
     */
    public function test_database_upgrade_missing_tables_does_fresh_install(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        $WBW_Database = new WBW_Database();

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 0;

        Functions\expect('\delete_option')->with('wbw_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('wbw_db_version',$WBW_Database->wbw_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn($WBW_Database->wbw_db_version);
        // fresh install should be performed
        $this->assertEquals($WBW_Database->wbw_db_upgrade($WBW_Database->wbw_db_version, false), 1);
        
    }

    /**
     * @covers wbw\WBW_Database\wbw_db_upgrade
     */
    public function test_database_upgrade_performs_upgrade_with_low_version(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        $WBW_Database = new WBW_Database();

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 1;

        Functions\expect('\delete_option')->with('wbw_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('wbw_db_version',$WBW_Database->wbw_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn("0.0.1");
        // upgrade should be performed
        $this->assertEquals($WBW_Database->wbw_db_upgrade($WBW_Database->wbw_db_version, false), 2);
        
    }

    /**
     * @covers wbw\WBW_Database\wbw_db_upgrade
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

        Functions\expect('\delete_option')->once()->andReturn(true);
        Functions\expect('\add_option')->once()->andReturn(true);

        $WBW_Database = new WBW_Database();
        Functions\when('\get_option')->justReturn(false);
        // fresh install should be performed
        $this->assertEquals($WBW_Database->wbw_db_upgrade($WBW_Database->wbw_db_version, false), 1);
    }


    /**
     * @covers wbw\WBW_Database\wbw_db_upgrade
     */
    public function test_database_upgrade_fresh_install_requested_does_fresh_install(): void
    {
        // @return int 0 if nothing was performed, 1 if fresh install was performed, 2 if upgrade was performed

        $WBW_Database = new WBW_Database();

        global $wpdb;
        $wpdb =  Mockery::mock('wpdb');
        /** @var Mockery::mock $wpdb test */
        $wpdb->shouldReceive('query');
        $wpdb->shouldReceive('get_charset_collate');
        $wpdb->num_rows = 1;

        Functions\expect('\delete_option')->with('wbw_db_version')->once()->andReturn(true);
        Functions\expect('\add_option')->with('wbw_db_version',$WBW_Database->wbw_db_version)->once()->andReturn(true);

        Functions\when('\get_option')->justReturn(false);
        // fresh install should be performed
        $this->assertEquals($WBW_Database->wbw_db_upgrade($WBW_Database->wbw_db_version, true), 1);
    }

}

<?php

declare(strict_types=1);

use wbw\Debug;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey;
use function Patchwork\{redefine, getFunction, always};

define('DEBUG_ENABLED',true);
define('DEBUG_DISABLED',false);

/**
 * @covers wbw\Debug
 */
final class DebugTest extends TestCase
{
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

        $this->setVerboseErrorHandler();
        $basedir = dirname(dirname(dirname(__FILE__)));
        require_once($basedir . '/vendor/antecedent/patchwork/Patchwork.php');

        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

// test for GET bmltserver (get server test settings)
    /**
     * @covers wbw\REST\Handlers\BMLTServerHandler::get_bmltserver_handler
     */
    public function test_debug_log_enabled(): void
    {
        define('WBW_DEBUG',true);
        $dbg = new Debug();
        Functions\expect('error_log')->once();

        $dbg->debug_log("hi");
        $this->assertTrue(true);
    }


}

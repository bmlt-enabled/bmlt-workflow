<?php
require_once('./vendor/antecedent/patchwork/Patchwork.php');
require_once('./vendor/autoload.php');

register_shutdown_function(function() {
    foreach ($GLOBALS['lastStack'] as $i => $frame) {
        print "{$i}. {$frame['file']} +{$frame['line']} in function {$frame['function']}\n";
    }
});
register_tick_function(function() {
    $GLOBALS['lastStack'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
});
declare(ticks=1);

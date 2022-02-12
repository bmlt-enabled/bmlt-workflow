<?php

function dbg($logmsg)
{
    $log = plugin_dir_path(__FILE__) . 'debug.log';
    error_log($logmsg . PHP_EOL, 3, $log);
}

dbg('webhook hit');
echo shell_exec('/home/ssm-user/clone.sh');
dbg('webhook executed');
?>
<?php

function dbg($logmsg)
{
    $log = '/var/www/html/flop/wp-content/plugins/meeting-admin-workflow/debug.log';
    error_log($logmsg . PHP_EOL, 3, $log);
}

dbg('webhook hit');
echo shell_exec('sudo -u ssm-user /home/ssm-user/clone.sh');
dbg('webhook executed');
?>
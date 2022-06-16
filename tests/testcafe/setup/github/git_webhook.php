<?php

// dbg('webhook hit');
echo shell_exec('sudo -u ssm-user /home/ssm-user/scripts/clone.sh');
// dbg('webhook executed');
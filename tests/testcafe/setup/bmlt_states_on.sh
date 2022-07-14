#!/bin/sh

sed 's/%MEETING_STATES%/\$meeting_states_and_provinces = array("NSW","SA","VIC");/g' /home/ssm-user/scripts/auto-config.inc.php.in > /var/www/html/blank_bmlt/auto-config.inc.php

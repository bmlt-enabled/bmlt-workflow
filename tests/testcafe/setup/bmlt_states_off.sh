#!/bin/sh

sed 's/%MEETING_STATES%//g' /home/ssm-user/scripts/auto-config.inc.php.in > /var/www/html/blank_bmlt/auto-config.inc.php

#!/bin/sh
# Copyright (C) 2022 nigel.bmlt@gmail.com
# 
# This file is part of bmlt-workflow.
# 
# bmlt-workflow is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# bmlt-workflow is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.

mysql -D blank_bmlt -ussm-user < /home/ssm-user/scripts/blank_bmlt.sql
export DB_PASSWORD=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name bmltwf_bmlt_db_password --value $DB_PASSWORD --type SecureString --region ap-southeast-2

# $dbPassword = '287352984739837'; // This is the password for the above authorized user. Make it a big, ugly hairy one. It is powerful, and there is no need to remember it.
sed 's/%DB_PASSWORD%/\$dbPassword = $DB_PASSWORD/g' /home/ssm-user/scripts/auto-config.inc.php.in > /tmp/auto-config.inc.php.$$
sed 's/%MEETING_STATES%//g' /tmp/auto-config.inc.php.$$ > /tmp/auto-config.inc.php.1.$$
sudo cp /tmp/auto-config.inc.php.1.$$ /var/www/html/blank_bmlt/auto-config.inc.php
rm /tmp/auto-config.inc.php.$$
mysql -D blank_bmlt -ussm-user -e "ALTER USER 'blank_bmlt'@'%' IDENTIFIED BY '$DB_PASSWORD';"

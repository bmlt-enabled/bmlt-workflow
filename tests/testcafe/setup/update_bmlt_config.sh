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

if [[ -z "${MEETING_STATES_ON}" ]]; then
  MS=""
else
  MS="${MEETING_STATES_ON}"
fi

if [[ -z "${AUTO_GEOCODING_ON}" ]]; then
  AG="\$auto_geocoding_enabled = false;"
else
  AG="\$auto_geocoding_enabled = true;"
fi

GK=\$gkey=\'$(aws ssm get-parameter --name bmltwf_gmaps_key --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r)\'\;

export DB_PASSWORD=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name bmltwf_bmlt_db_password --value $DB_PASSWORD --type SecureString --region ap-southeast-2
mysql -D blank_bmlt -ussm-user -e "ALTER USER 'blank_bmlt'@'%' IDENTIFIED BY '$DB_PASSWORD';"

DB=\$dbPassword=\'$DB_PASSWORD\'\;

# %DB_PASSWORD%
# %MEETING_STATES% 
# %GMAPS_KEY%
# $AUTO_GEOCODING%

sed "s/%DB_PASSWORD%/$DB/g" /home/ssm-user/scripts/auto-config.inc.php.in | sed "s/%MEETING_STATES%/$MS/g" | sed "s/%GMAPS_KEY%/$GK/g" | sed "s/%AUTO_GEOCODING%/$AG/g"  > /tmp/auto-config.inc.$$
sudo cp /tmp/auto-config.inc.$$  /var/www/html/blank_bmlt/auto-config.inc.php
rm /tmp/auto-config.inc.$$

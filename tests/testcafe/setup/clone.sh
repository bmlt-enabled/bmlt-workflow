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

# git clone https://github.com/bmlt-enabled/wordpress-bmlt-workflow.git
#
#
cd /home/ssm-user
cd wordpress-bmlt-workflow
git pull
cp tests/testcafe/setup/github/* /var/www/html/github
cp tests/testcafe/setup/*.sh /home/ssm-user/scripts
cp tests/testcafe/setup/*.sql /home/ssm-user/scripts
chmod 755 /home/ssm-user/scripts/*.sh
cd ..
rm -rf /home/ssm-user/wbw/*
cp -R wordpress-bmlt-workflow/* wbw
cd wbw
sed -i "s/define('WBW_DEBUG', false);/define('WBW_DEBUG', true);/g" config.php
/usr/local/bin/composer dumpautoload
/home/ssm-user/scripts/wp-install.sh
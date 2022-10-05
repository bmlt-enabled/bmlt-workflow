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

export PLUGINDIR=/var/www/html/flop/wp-content/plugins/bmlt-workflow
export BRANCH=1.0.7-fixes

cd /home/ssm-user
git clone https://github.com/bmlt-enabled/bmlt-workflow.git
cd bmlt-workflow
git switch $BRANCH
git pull
cp tests/testcafe/setup/github/* /var/www/html/github
cp tests/testcafe/setup/*.sh /home/ssm-user/scripts
cp tests/testcafe/setup/*.sql /home/ssm-user/scripts
chmod 755 /home/ssm-user/scripts/*.sh
cd ..
sudo rm -rf $PLUGINDIR
sudo mkdir $PLUGINDIR
sudo chown ssm-user:ssm-user $PLUGINDIR
sudo cp -R bmlt-workflow/* $PLUGINDIR
cd $PLUGINDIR
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" config.php
/usr/local/bin/composer dumpautoload
sudo chown -R apache:apache $PLUGINDIR
/home/ssm-user/scripts/wp-install.sh
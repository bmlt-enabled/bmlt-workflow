# git clone https://github.com/bmlt-enabled/wordpress-bmlt-workflow.git
#
cd /home/ssm-user
cd wordpress-bmlt-workflow
git pull
cp tests/testcafe/setup/github/* /var/www/html/github
cp tests/testcafe/setup/*.sh /home/ssm-user/scripts
cp tests/testcafe/setup/*.sql /home/ssm-user/scripts
cd ..
rm -rf /home/ssm-user/wbw/*
cp -R wordpress-bmlt-workflow/* wbw
cd wbw
sed -i "s/define('WBW_DEBUG', false);/define('WBW_DEBUG', true);/g" config.php
/usr/local/bin/composer dumpautoload
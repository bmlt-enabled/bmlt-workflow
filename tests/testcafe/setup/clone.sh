# git clone https://github.com/bmlt-enabled/wordpress-bmlt-workflow.git
cd /home/ssm-user
cd wordpress-bmlt-workflow
git pull
cd ..
rm -rf /home/ssm-user/wbw/*
cp -R wordpress-bmlt-workflow/* wbw
cd wbw
sed -i "s/define('WBW_DEBUG', false);/define('WBW_DEBUG', true);/g" config.php
/usr/local/bin/composer dumpautoload
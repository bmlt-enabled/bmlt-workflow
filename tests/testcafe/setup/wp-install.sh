#!/bin/bash -x

# Install script for Latest WordPress on local dev

# Setup

# Hardcoded variables that shouldn't change much

# Path to MySQL
MYSQL='/usr/bin/mysql'

export mysqlhost=localhost
export mysqldb=wpdevdb
export mysqluser=wpdevuser
#export mysqlpass=0193019348109384098
export mysqlpass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
export wptitle=devsite
export wpuser=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --name wbw_test_wpuser --value $wpuser --type SecureString --region ap-southeast-2
export wppass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --name wbw_test_wppass --value $wppass --type SecureString --region ap-southeast-2
export wpemail=nigel.brittain@gmail.com
export siteurl=54.153.167.239/wordpressdev
export BRANCH=0.4.0-fixes

$MYSQL -e "DROP DATABASE $mysqldb"
# Setup DB & DB User
$MYSQL -e "CREATE DATABASE IF NOT EXISTS $mysqldb; GRANT ALL ON $mysqldb.* TO '$mysqluser'@'$mysqlhost' IDENTIFIED BY '$mysqlpass'; FLUSH PRIVILEGES "

# Download latest WordPress and uncompress
cd /home/ssm-user/wordpress
rm -rf /home/ssm-user/wordpress/wordpress
rm /home/ssm-user/wordpress/latest.tar.gz
wget http://wordpress.org/latest.tar.gz
tar zxf latest.tar.gz
rm -rf /var/www/html/wordpressdev
mkdir /var/www/html/wordpressdev
mv wordpress/* /var/www/html/wordpressdev
cd /var/www/html/wordpressdev


# Build our wp-config.php file
sed -e "s/localhost/"$mysqlhost"/" -e "s/database_name_here/"$mysqldb"/" -e "s/username_here/"$mysqluser"/" -e "s/password_here/"$mysqlpass"/" wp-config-sample.php > wp-config.php

# Grab our Salt Keys
SALT=$(curl -L https://api.wordpress.org/secret-key/1.1/salt/)
STRING='put your unique phrase here'
printf '%s\n' "g/$STRING/d" a "$SALT" . w | ed -s wp-config.php

# Run our install ...
curl -d "weblog_title=$wptitle&user_name=$wpuser&admin_password=$wppass&admin_password2=$wppass&admin_email=$wpemail" http://$siteurl/wp-admin/install.php?step=2

# install our plugin
cd /home/ssm-user/wordpress
git clone https://github.com/bmlt-enabled/wordpress-bmlt-workflow.git
mv wordpress-bmlt-workflow /var/www/html/wordpressdev/wp-content/plugins
cd /var/www/html/wordpressdev/wp-content/plugins/wordpress-bmlt-workflow
git switch $BRANCH
sed -i "s/define('WBW_DEBUG', false);/define('WBW_DEBUG', true);/g" config.php
# activate plugin
wp plugin activate --path=/var/www/html/wordpressdev "wordpress-bmlt-workflow"
wp option --path=/var/www/html/wordpressdev add 'wbw_bmlt_server_address' 'http://54.153.167.239/blank_bmlt/main_server/'
wp option --path=/var/www/html/wordpressdev add 'wbw_bmlt_username' 'bmlt-workflow-bot'
wp option --path=/var/www/html/wordpressdev add 'wbw_bmlt_test_status' 'success'
wp option --path=/var/www/html/wordpressdev add 'wbw_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --path=/var/www/html/wordpressdev --post_type=page --post_title='testpage' --post_content='[wbw-meeting-update-form]' --post_status='publish' --post_name='testpage'
# Tidy up
exit

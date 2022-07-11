#!/bin/bash -x

# Install script for Latest WordPress on local dev

# Setup
export PATH=/usr/local/bin:$PATH
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
aws ssm put-parameter --overwrite --name wbw_test_wpuser --value $wpuser --type SecureString --region ap-southeast-2
export wppass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name wbw_test_wppass --value $wppass --type SecureString --region ap-southeast-2
export wpemail=nigel.brittain@gmail.com
export siteurl=54.153.167.239/wordpressdev
export BRANCH=0.4.3-fixes

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
cat > insert << EOF
// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', '/home/ssm-user/php-errors.log' );

// Use dev versions of core JS and CSS files (only needed if you are modifying these core files)
define( 'SCRIPT_DEBUG', true );
@ini_set('log_errors','On'); // enable or disable php error logging (use 'On' or 'Off')
@ini_set('display_errors','On'); // enable or disable public display of errors (use 'On' or 'Off')
@ini_set('mail.log','/home/ssm-user/mail.log'); // path to server-writable log file
@ini_set('sendmail_path','/home/ssm-user/maillog.py'); // path to server-writable log file

EOF
cat wp-config-sample.php | tr -d '\r' | sed -e "s/localhost/"$mysqlhost"/" -e "s/database_name_here/"$mysqldb"/" -e "s/username_here/"$mysqluser"/" -e "s/password_here/"$mysqlpass"/" | sed -e '/\/\* Add any custom values between this line and the "stop editing" line. \*\//r./insert' > wp-config.php
rm insert

# Grab our Salt Keys
SALT=$(curl -L https://api.wordpress.org/secret-key/1.1/salt/ | sed -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '4hJ:ZRFUAdfFEBq=z\$9+]Bk|\!1y8V,h#w4aNGy~o7u|BBR;u(ASi],u[Cp46qRQa');/")
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

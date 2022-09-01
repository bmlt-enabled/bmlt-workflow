#!/bin/bash -x
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

export PATH=/usr/local/bin:$PATH
export MYSQL='/usr/bin/mysql'

export BRANCH=1.0.2-fixes

export mysqlhost=localhost
export mysqldb=wpdevdb
export mysqluser=wpdevuser
export mysqlpass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
export wptitle=devsite
export wpuser=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name bmltwf_test_wpuser --value $wpuser --type SecureString --region ap-southeast-2
export wppass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name bmltwf_test_wppass --value $wppass --type SecureString --region ap-southeast-2
export wpemail=nigel.brittain@gmail.com
export sitename=wordpressdev
export siteurl=http://54.153.167.239/$sitename
export sitelocalpath=/var/www/html/$sitename

sudo rm -rf $sitelocalpath

$MYSQL -e "DROP DATABASE $mysqldb"
# Setup DB & DB User
$MYSQL -e "CREATE DATABASE IF NOT EXISTS $mysqldb; GRANT ALL ON $mysqldb.* TO '$mysqluser'@'$mysqlhost' IDENTIFIED BY '$mysqlpass'; FLUSH PRIVILEGES "

wp core download --path=$sitelocalpath
wp config create --path=$sitelocalpath --dbname=$mysqldb --dbuser=$mysqluser --dbpass=$mysqlpass
wp core install --url=$siteurl --title="hi" --admin_user=$wpuser --admin_password=$wppass --admin_email=$wpemail --path=$sitelocalpath

cd $sitelocalpath

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
sed -i -e '/\/\* Add any custom values between this line and the "stop editing" line. \*\//r./insert' wp-config.php
rm insert
sed -i -e "/define( 'WP_DEBUG', false );/d" wp-config.php

# Grab our Salt Keys
sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '4hJ:ZRFUAdfFEBq=z\$9+]Bk|\!1y8V,h#w4aNGy~o7u|BBR;u(ASi],u[Cp46qRQa');/" wp-config.php

sudo chown -R apache:apache $sitelocalpath

# install our plugin
cd /home/ssm-user/wordpress
git clone https://github.com/bmlt-enabled/bmlt-workflow.git
cd bmlt-workflow
git switch $BRANCH
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" config.php
cd ..
sudo mv bmlt-workflow $sitelocalpath/wp-content/plugins
sudo chown -R apache:apache $sitelocalpath/wp-content/plugins/bmlt-workflow
cd $sitelocalpath/wp-content/plugins/bmlt-workflow

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://54.153.167.239/blank_bmlt/main_server/'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'

## MULTI SITE INSTALLER (single site test)

export mysqlhost=localhost
export mysqldb=wpmultidevdb
export mysqluser=wpmultidevuser
export mysqlpass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
export wptitle=multidevsite
export wpuser=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name multi_bmltwf_test_wpuser --value $wpuser --type SecureString --region ap-southeast-2
export wppass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name multi_bmltwf_test_wppass --value $wppass --type SecureString --region ap-southeast-2
export wpemail=nigel.brittain@gmail.com
export sitename=wordpressmultidev
export siteurl=http://54.153.167.239/$sitename/
export sitelocalpath=/var/www/html/$sitename

sudo rm -rf $sitelocalpath

$MYSQL -e "DROP DATABASE $mysqldb"
# Setup DB & DB User
$MYSQL -e "CREATE DATABASE IF NOT EXISTS $mysqldb; GRANT ALL ON $mysqldb.* TO '$mysqluser'@'$mysqlhost' IDENTIFIED BY '$mysqlpass'; FLUSH PRIVILEGES "

wp core download --path=$sitelocalpath
wp config create --path=$sitelocalpath --dbname=$mysqldb --dbuser=$mysqluser --dbpass=$mysqlpass
wp core multisite-install --base=/$sitename/ --url=$siteurl --title="hi" --admin_user=$wpuser --admin_password=$wppass --admin_email=$wpemail --path=$sitelocalpath
#wp core install --url=$siteurl --title="hi" --admin_user=$wpuser --admin_password=$wppass --admin_email=$wpemail --path=$sitelocalpath

cd $sitelocalpath

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
sed -i -e '/\/\* Add any custom values between this line and the "stop editing" line. \*\//r./insert' wp-config.php
rm insert

sed -i -e "/define( 'WP_DEBUG', false );/d" wp-config.php
#sed -i -e "s/.*PATH_CURRENT_SITE.*/define( 'PATH_CURRENT_SITE','\/wordpressmultidev\/');/" wp-config.php

cat > .htaccess << EOF
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /wordpressmultidev/
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ \$1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) \$2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ \$2 [L]
RewriteRule . index.php [L]
EOF

# Grab our Salt Keys
sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '4hJ:ZRFUAdfFEBq=z\$9+]Bk|\!1y8V,h#w4aNGy~o7u|BBR;u(ASi],u[Cp46qRQa');/" wp-config.php

sudo chown -R apache:apache $sitelocalpath

# install our plugin
cd /home/ssm-user/wordpress
git clone https://github.com/bmlt-enabled/bmlt-workflow.git
cd bmlt-workflow
git switch $BRANCH
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" config.php
cd ..
sudo mv bmlt-workflow $sitelocalpath/wp-content/plugins
sudo chown -R apache:apache $sitelocalpath/wp-content/plugins/bmlt-workflow
cd $sitelocalpath/wp-content/plugins/bmlt-workflow

wp --path=$sitelocalpath site create --slug=plugin
wp --path=$sitelocalpath site create --slug=noplugin
export pluginsite=${siteurl}plugin
# activate plugin
wp plugin activate --url=$pluginsite --path=$sitelocalpath "bmlt-workflow"
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://54.153.167.239/blank_bmlt/main_server/'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --url=$pluginsite --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'

## MULTI SITE INSTALLER (network wide install test)

export mysqlhost=localhost
export mysqldb=wpmultinetworkdevdb
export mysqluser=wpmultinetworkdevuser
export mysqlpass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
export wptitle=multinetworkdevsite
export wpuser=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name multi_bmltwf_test_wpuser --value $wpuser --type SecureString --region ap-southeast-2
export wppass=$(((RANDOM<<15|$RANDOM)<<15|$RANDOM))
aws ssm put-parameter --overwrite --name multi_bmltwf_test_wppass --value $wppass --type SecureString --region ap-southeast-2
export wpemail=nigel.brittain@gmail.com
export sitename=wordpressmultinetworkdev
export siteurl=http://54.153.167.239/$sitename/
export sitelocalpath=/var/www/html/$sitename

sudo rm -rf $sitelocalpath

$MYSQL -e "DROP DATABASE $mysqldb"
# Setup DB & DB User
$MYSQL -e "CREATE DATABASE IF NOT EXISTS $mysqldb; GRANT ALL ON $mysqldb.* TO '$mysqluser'@'$mysqlhost' IDENTIFIED BY '$mysqlpass'; FLUSH PRIVILEGES "

wp core download --path=$sitelocalpath
wp config create --path=$sitelocalpath --dbname=$mysqldb --dbuser=$mysqluser --dbpass=$mysqlpass
wp core multisite-install --base=/$sitename/ --url=$siteurl --title="hi" --admin_user=$wpuser --admin_password=$wppass --admin_email=$wpemail --path=$sitelocalpath
#wp core install --url=$siteurl --title="hi" --admin_user=$wpuser --admin_password=$wppass --admin_email=$wpemail --path=$sitelocalpath

cd $sitelocalpath

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
sed -i -e '/\/\* Add any custom values between this line and the "stop editing" line. \*\//r./insert' wp-config.php
rm insert

sed -i -e "/define( 'WP_DEBUG', false );/d" wp-config.php
#sed -i -e "s/.*PATH_CURRENT_SITE.*/define( 'PATH_CURRENT_SITE','\/wordpressmultidev\/');/" wp-config.php

cat > .htaccess << EOF
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /wordpressmultidev/
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ \$1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) \$2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ \$2 [L]
RewriteRule . index.php [L]
EOF

# Grab our Salt Keys
sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '4hJ:ZRFUAdfFEBq=z\$9+]Bk|\!1y8V,h#w4aNGy~o7u|BBR;u(ASi],u[Cp46qRQa');/" wp-config.php

sudo chown -R apache:apache $sitelocalpath

# install our plugin
cd /home/ssm-user/wordpress
git clone https://github.com/bmlt-enabled/bmlt-workflow.git
cd bmlt-workflow
git switch $BRANCH
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" config.php
cd ..
sudo mv bmlt-workflow $sitelocalpath/wp-content/plugins
sudo chown -R apache:apache $sitelocalpath/wp-content/plugins/bmlt-workflow
cd $sitelocalpath/wp-content/plugins/bmlt-workflow

wp --path=$sitelocalpath site create --slug=plugin
wp --path=$sitelocalpath site create --slug=noplugin
export pluginsite=${siteurl}plugin
# network activate plugin
wp plugin activate --network --path=$sitelocalpath "bmlt-workflow"
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://54.153.167.239/blank_bmlt/main_server/'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --url=$pluginsite --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'

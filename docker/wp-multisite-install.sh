#!/bin/sh -x 

export sitelocalpath=/var/www/html/$WORDPRESS_HOST
export siteurl=http://$WORDPRESS_HOST:$WORDPRESS_PORT/$WORDPRESS_HOST/

cd $sitelocalpath
wp core download
export DONE=1
while [ $DONE -ne 0 ]
do
    wp config create --path=$sitelocalpath --dbname=$WORDPRESS_DB_NAME --dbuser=$WORDPRESS_DB_USER --dbpass=$WORDPRESS_DB_PASSWORD --dbhost=$WORDPRESS_DB_HOST
    DONE=$?
    sleep 1
done

cat > $sitelocalpath/.htaccess << EOF
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /placeholder/
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

CUR=`pwd`; cd /tmp
sed -i -e "s/RewriteBase \/placeholder\//RewriteBase \/$WORDPRESS_HOST\//" $sitelocalpath/.htaccess
cd $CUR

wp db create
wp core multisite-install --path=$sitelocalpath --base=/$WORDPRESS_HOST/ --url=$siteurl --title="hi" --admin_user=admin --admin_password=admin --admin_email=a@a.com

CUR=`pwd`; cd /tmp
sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '$WORDPRESS_NONCE_SALT');/" $sitelocalpath/wp-config.php
cd $CUR

# create sites
export pluginsite=${siteurl}plugin
wp --path=$sitelocalpath --url=${pluginsite} site create --slug=plugin

if [ ! -z $NOPLUGIN ]
then
    export pluginsite2=${siteurl}noplugin
    wp --path=$sitelocalpath --url=${pluginsite2} site create --slug=noplugin
else 
    export pluginsite2=${siteurl}plugin2
    wp --path=$sitelocalpath --url=${pluginsite2} site create --slug=plugin2
fi

# hack for multisite
mysql --host=$WORDPRESS_DB_HOST -u $WORDPRESS_DB_USER -D $WORDPRESS_DB_NAME --password=$WORDPRESS_DB_PASSWORD -e 'update wp_blogs set domain="wordpress-php8-multisitesingle:81" where domain="wordpress-php8-multisitesingle81";'
mysql --host=$WORDPRESS_DB_HOST -u $WORDPRESS_DB_USER -D $WORDPRESS_DB_NAME --password=$WORDPRESS_DB_PASSWORD -e 'update wp_2_options set option_value="http://wordpress-php8-multisitesingle:81/wordpress-php8-multisitesingle/plugin" where option_name="siteurl";'
mysql --host=$WORDPRESS_DB_HOST -u $WORDPRESS_DB_USER -D $WORDPRESS_DB_NAME --password=$WORDPRESS_DB_PASSWORD -e 'update wp_2_options set option_value="http://wordpress-php8-multisitesingle:81/wordpress-php8-multisitesingle/plugin" where option_name="home";'
mysql --host=$WORDPRESS_DB_HOST -u $WORDPRESS_DB_USER -D $WORDPRESS_DB_NAME --password=$WORDPRESS_DB_PASSWORD -e 'update wp_3_options set option_value="http://wordpress-php8-multisitesingle:81/wordpress-php8-multisitesingle/noplugin" where option_name="siteurl";'
mysql --host=$WORDPRESS_DB_HOST -u $WORDPRESS_DB_USER -D $WORDPRESS_DB_NAME --password=$WORDPRESS_DB_PASSWORD -e 'update wp_3_options set option_value="http://wordpress-php8-multisitesingle:81/wordpress-php8-multisitesingle/noplugin" where option_name="home";'

CUR=`pwd`; cd /tmp
sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" $sitelocalpath/wp-content/plugins/bmlt-workflow/config.php
cd $CUR

# activate plugin
wp plugin activate --url=$pluginsite --path=$sitelocalpath "bmlt-workflow"
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'${BMLT}':'${BMLT_PORT}'/main_server/'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --url=$pluginsite --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# site 2

if [ -z $NOPLUGIN ]
then
    wp plugin activate --url=$pluginsite2 --path=$sitelocalpath "bmlt-workflow"
    wp option --url=$pluginsite2 --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'${BMLT}':'${BMLT_PORT}'/main_server/'
    wp option --url=$pluginsite2 --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
    wp option --url=$pluginsite2 --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
    wp option --url=$pluginsite2 --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
fi

# create our test page
wp post create --url=$pluginsite --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'
# create our test users
wp user create --url=$pluginsite --path=$sitelocalpath submitpriv aa123@a.com --user_pass=submitpriv
wp user create --url=$pluginsite --path=$sitelocalpath nopriv aa456@a.com --user_pass=nopriv


cat >/usr/local/etc/php/conf.d/error-logging.ini <<EOF
error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
log_errors_max_len = 1024
ignore_repeated_errors = On
ignore_repeated_source = Off
html_errors = Off
EOF

rm /var/log/php_errors.log
touch /var/log/php_errors.log
chmod 777 /var/log/php_errors.log

CUR=`pwd`; cd /tmp
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-enabled/000-default.conf 
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-available/000-default.conf 
sed -i "s/Listen 80/Listen $WORDPRESS_PORT/g" /etc/apache2/ports.conf 
cd $CUR

echo "<?php phpinfo();" >> /var/www/html/a.php

apache2-foreground

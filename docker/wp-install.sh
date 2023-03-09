#!/bin/sh -x 

export sitelocalpath=/var/www/html
cd $sitelocalpath
wp core download

DONE=1

if [ $WORDPRESS_PORT -eq '80' ]
then
    URL=http://$WORDPRESS_HOST
 else
    URL=http://$WORDPRESS_HOST:$WORDPRESS_PORT
fi

while [ $DONE -ne 0 ]
do
    wp config create --path=$sitelocalpath --dbname=$WORDPRESS_DB_NAME --dbuser=$WORDPRESS_DB_USER --dbpass=$WORDPRESS_DB_PASSWORD --dbhost=$WORDPRESS_DB_HOST
    DONE=$?
    sleep 1
done

wp db create
wp core install --url=$URL --title="hi" --admin_user=admin --admin_password=admin --admin_email=a@a.com --path=/var/www/html
wp language core install fr_FR

mkdir /var/www/html/wp-content/plugins/bmlt-workflow
sync-plugin.sh

sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '$WORDPRESS_NONCE_SALT');/" /var/www/html/wp-config.php

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'${BMLT}':'${BMLT_PORT}'/main_server/'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json

# create our test page
wp post create --path=$sitelocalpath --post_type=page --post_name='testpage' --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'
# create our test users
wp user create --path=$sitelocalpath submitpriv aa123@a.com --user_pass=submitpriv
wp user create --path=$sitelocalpath nopriv aa456@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 1 1@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 2 2@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 3 3@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 4 4@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 5 5@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 6 6@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 7 7@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 8 8@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 9 9@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 10 10@a.com --user_pass=nopriv
wp user create --path=$sitelocalpath 11 11@a.com --user_pass=nopriv

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

sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-enabled/000-default.conf

sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-available/000-default.conf

sed -i "s/Listen 80/Listen $WORDPRESS_PORT/g" /etc/apache2/ports.conf

echo "<?php phpinfo();" >> /var/www/html/a.php


apache2-foreground


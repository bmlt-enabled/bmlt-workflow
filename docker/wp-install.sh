#!/bin/sh -x 

export sitelocalpath=/var/www/html
export bmltip=`getent hosts bmlt2x | awk '{print $1}'`
cd $sitelocalpath
wp core download
export DONE=1
while [ $DONE -ne 0 ]
do
    wp config create --path=$sitelocalpath --dbname=$WORDPRESS_DB_NAME --dbuser=$WORDPRESS_DB_USER --dbpass=$WORDPRESS_DB_PASSWORD --dbhost=$WORDPRESS_DB_HOST
    DONE=$?
    sleep 1
done
wp core install --url=http://$WORDPRESS_HOST:$WORDPRESS_PORT --title="hi" --admin_user=admin --admin_password=admin --admin_email=a@a.com --path=/var/www/html

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'$bmltip':8000/main_server/'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'
# create our test users
wp user create --path=$sitelocalpath submission aa123@a.com --user_pass=submission
wp user create --path=$sitelocalpath nopriv aa456@a.com --user_pass=nopriv

sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" /var/www/html/wp-content/plugins/bmlt-workflow/config.php

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

touch /var/log/php_errors.log
chmod 777 /var/log/php_errors.log

sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-enabled/000-default.conf 
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-available/000-default.conf 
sed -i "s/Listen 80/Listen $WORDPRESS_PORT/g" /etc/apache2/ports.conf 

echo "<?php phpinfo();" >> /var/www/html/a.php

apache2-foreground

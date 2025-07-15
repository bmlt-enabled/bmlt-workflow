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

/usr/local/bin/import-sql.sh
wp option --path=$sitelocalpath update "bmltwf_bmlt_server_address" "http://192.168.86.237:3006/main_server/"
wp option --path=$sitelocalpath update "bmltwf_bmlt_username" "bmlt-workflow-bot"
wp option --path=$sitelocalpath update 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
wp option --path=$sitelocalpath update "bmltwf_bmlt_test_status" "success"
wp option --path=$sitelocalpath update "bmltwf_submitter_email_template"  "<p><br>Thank you for submitting the online meeting update.<br>We will usually be able action your\r\n    request within 48 hours.<br>Our process also updates NA websites around Australia and at NA World Services.<br>\r\n<\/p>\r\n<hr><br>\r\n<table class=\"blueTable\" style=\"border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;\">\r\n    <thead style=\"background: #1C6EA4;border-bottom: 2px solid #444444;\">\r\n        <tr>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;\">\r\n                <br>Field Name\r\n            <\/th>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;\">\r\n                <br>Value\r\n            <\/th>\r\n        <\/tr>\r\n    <\/thead>\r\n    <tbody>\r\n        {field:submission}\r\n    <\/tbody>\r\n<\/table>"
wp option --path=$sitelocalpath update "bmltwf_optional_location_province" "display"
wp option --path=$sitelocalpath update "bmltwf_optional_location_sub_province" "hidden"
wp option --path=$sitelocalpath update "bmltwf_optional_location_nation" "hidden"
wp option --path=$sitelocalpath update "bmltwf_delete_closed_meetings" "unpublish"
wp option --path=$sitelocalpath update "bmltwf_email_from_address" "Test <test@test.org>"
wp option --path=$sitelocalpath update "bmltwf_fso_email_template" "<p>Attn: FSO.<br>\r\nPlease send a starter kit to the following meeting:\r\n<\/p>\r\n<hr><br>\r\n<table class=\"blueTable\" style=\"border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;\">\r\n    <thead style=\"background: #1C6EA4;border-bottom: 2px solid #444444;\">\r\n        <tr>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;\">\r\n                <br>Field Name\r\n            <\/th>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;\">\r\n                <br>Value\r\n            <\/th>\r\n        <\/tr>\r\n    <\/thead>\r\n    <tbody>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Group Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Requester First Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:first_name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Requester Last Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:last_name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Starter Kit Postal Address<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:starter_kit_postal_address}\r\n            <\/td>\r\n        <\/tr>\r\n    <\/tbody>\r\n<\/table>"
wp option --path=$sitelocalpath update "bmltwf_fso_email_address" ""

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
# wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'${BMLT}':'${BMLT_PORT}'/main_server/'
# wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
# wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
# wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json

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
cp /usr/local/bin/import-sql.php /var/www/html
cp /usr/local/bin/test-upgrade.php /var/www/html

apache2-foreground


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
cp -f /usr/local/lib/htaccess.multisite /var/www/html/.htaccess
wp core multisite-install --base=/$WORDPRESS_HOST/ --url=http://$WORDPRESS_HOST/$WORDPRESS_HOST/ --title="hi" --admin_user=admin --admin_password=admin --admin_email=a@a.com --path=/var/www/html

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

apache2-foreground

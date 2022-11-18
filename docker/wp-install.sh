#!/bin/sh -x 

export sitelocalpath=/var/www/html
wp db reset --path=$sitelocalpath --yes
export bmltip=`getent hosts bmlt3x | awk '{print $1}'`
wp core install --url=http://localhost:8084 --title="hi" --admin_user=admin --admin_password=admin --admin_email=a@a.com --path=/var/www/html

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'$bmltip':8080/main_server/'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json
# create our test page
wp post create --path=$sitelocalpath --post_type=page --post_title='testpage' --post_content='[bmltwf-meeting-update-form]' --post_status='publish' --post_name='testpage'
# create our test users
wp user create --path=$sitelocalpath submission aa123@a.com --user_pass=submission
wp user create --path=$sitelocalpath nopriv aa456@a.com --user_pass=nopriv

apache2-foreground

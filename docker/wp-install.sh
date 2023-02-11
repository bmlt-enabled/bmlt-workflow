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

sed -i -e "s/.*NONCE_SALT.*/define('NONCE_SALT',       '$WORDPRESS_NONCE_SALT');/" /var/www/html/wp-config.php

# activate plugin
wp plugin activate --path=$sitelocalpath "bmlt-workflow"
wp option --path=$sitelocalpath add 'bmltwf_bmlt_server_address' 'http://'${BMLT}':'${BMLT_PORT}'/main_server/'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_username' 'bmlt-workflow-bot'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_test_status' 'success'
wp option --path=$sitelocalpath add 'bmltwf_bmlt_password' '{"config":{"size":"MzI=","salt":"\/5ObzNuYZ\/Y5aoYTsr0sZw==","limit_ops":"OA==","limit_mem":"NTM2ODcwOTEy","alg":"Mg==","nonce":"VukDVzDkAaex\/jfB"},"encrypted":"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw=="}' --format=json

# install crouton
wp plugin --path=$sitelocalpath install crouton
wp plugin activate --path=$sitelocalpath "crouton"
wp post create --path=$sitelocalpath --post_type=page --post_title='crouton' --post_content='[bmlt_tabs]' --post_status='publish' --post_name='crouton' --meta_input='{"_wp_page_template":"blank"}'
wp option delete --path=$sitelocalpath bmlt_tabs_options
wp option update --path=$sitelocalpath --format=json bmlt_tabs_options <<EOF
{
  "root_server": "http://${BMLT}:${BMLT_PORT}/main_server/",
  "service_body_1":"Mid-Hudson Area Service,1009,1046,ABCD Region",
  "custom_query": "",
  "custom_css": "",
  "meeting_data_template": "{{#isTemporarilyClosed this}}\r\n    <div class='temporarilyClosed'><span class='glyphicon glyphicon-flag'></span> {{temporarilyClosed this}}</div>\r\n{{/isTemporarilyClosed}}\r\n<div class='meeting-name'>{{this.meeting_name}}</div>\r\n<div class='location-text'>{{this.location_text}}</div>\r\n<div class='meeting-address'>{{this.formatted_address}}</div>\r\n<div class='location-information'>{{this.formatted_location_info}}</div>\r\n{{#if this.virtual_meeting_additional_info}}\r\n    <div class='meeting-additional-info'>{{this.virtual_meeting_additional_info}}</div>\r\n{{/if}}",
  "metadata_template": "{{#isVirtualOrHybrid this}}\r\n    {{#isHybrid this}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud-upload'></span> {{meetsHybrid this}}</div>\r\n    {{else}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud'></span> {{meetsVirtually this}}</div>\r\n    {{/isHybrid}}\r\n    {{#if this.virtual_meeting_link}}\r\n        <div><span class='glyphicon glyphicon-globe'></span> {{webLinkify this.virtual_meeting_link}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.virtual_meeting_link}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n    {{#if this.phone_meeting_number}}\r\n        <div><span class='glyphicon glyphicon-earphone'></span> {{phoneLinkify this.phone_meeting_number}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.phone_meeting_number}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n{{/isVirtualOrHybrid}}\r\n{{#isNotTemporarilyClosed this}}\r\n    {{#unless (hasFormats 'VM' this)}}\r\n        <div>\r\n            <a id='map-button' class='btn btn-primary btn-xs'\r\n                href='https://www.google.com/maps/search/?api=1&query={{this.latitude}},{{this.longitude}}&q={{this.latitude}},{{this.longitude}}'\r\n                target='_blank' rel='noopener noreferrer'>\r\n                <span class='glyphicon glyphicon-map-marker'></span> {{this.map_word}}</a>\r\n        </div>\r\n        <div class='geo hide'>{{this.latitude}},{{this.longitude}}</div>\r\n    {{/unless}}\r\n{{/isNotTemporarilyClosed}}",
  "theme": "",
  "recurse_service_bodies": "0",
  "extra_meetings": [],
  "extra_meetings_enabled": "0",
  "google_api_key": ""
}
EOF

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

rm /var/log/php_errors.log
touch /var/log/php_errors.log
chmod 777 /var/log/php_errors.log

sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-enabled/000-default.conf 
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$WORDPRESS_PORT>/g" /etc/apache2/sites-available/000-default.conf 
sed -i "s/Listen 80/Listen $WORDPRESS_PORT/g" /etc/apache2/ports.conf 

echo "<?php phpinfo();" >> /var/www/html/a.php
cat << END >> /var/www/html/crouton2x.sh
#!/bin/sh
wp option update --path=$sitelocalpath --format=json bmlt_tabs_options <<EOF
{
  "root_server": "http://bmlt2x:8000/main_server/",
  "service_body_1":"Mid-Hudson Area Service,1009,1046,ABCD Region",
  "custom_query": "",
  "custom_css": "",
  "meeting_data_template": "{{#isTemporarilyClosed this}}\r\n    <div class='temporarilyClosed'><span class='glyphicon glyphicon-flag'></span> {{temporarilyClosed this}}</div>\r\n{{/isTemporarilyClosed}}\r\n<div class='meeting-name'>{{this.meeting_name}}</div>\r\n<div class='location-text'>{{this.location_text}}</div>\r\n<div class='meeting-address'>{{this.formatted_address}}</div>\r\n<div class='location-information'>{{this.formatted_location_info}}</div>\r\n{{#if this.virtual_meeting_additional_info}}\r\n    <div class='meeting-additional-info'>{{this.virtual_meeting_additional_info}}</div>\r\n{{/if}}",
  "metadata_template": "{{#isVirtualOrHybrid this}}\r\n    {{#isHybrid this}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud-upload'></span> {{meetsHybrid this}}</div>\r\n    {{else}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud'></span> {{meetsVirtually this}}</div>\r\n    {{/isHybrid}}\r\n    {{#if this.virtual_meeting_link}}\r\n        <div><span class='glyphicon glyphicon-globe'></span> {{webLinkify this.virtual_meeting_link}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.virtual_meeting_link}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n    {{#if this.phone_meeting_number}}\r\n        <div><span class='glyphicon glyphicon-earphone'></span> {{phoneLinkify this.phone_meeting_number}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.phone_meeting_number}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n{{/isVirtualOrHybrid}}\r\n{{#isNotTemporarilyClosed this}}\r\n    {{#unless (hasFormats 'VM' this)}}\r\n        <div>\r\n            <a id='map-button' class='btn btn-primary btn-xs'\r\n                href='https://www.google.com/maps/search/?api=1&query={{this.latitude}},{{this.longitude}}&q={{this.latitude}},{{this.longitude}}'\r\n                target='_blank' rel='noopener noreferrer'>\r\n                <span class='glyphicon glyphicon-map-marker'></span> {{this.map_word}}</a>\r\n        </div>\r\n        <div class='geo hide'>{{this.latitude}},{{this.longitude}}</div>\r\n    {{/unless}}\r\n{{/isNotTemporarilyClosed}}",
  "theme": "",
  "recurse_service_bodies": "0",
  "extra_meetings": [],
  "extra_meetings_enabled": "0",
  "google_api_key": ""
}
EOF
END
cat << END >> /var/www/html/crouton3x.sh
#!/bin/sh
wp option update --path=$sitelocalpath --format=json bmlt_tabs_options <<EOF
{
  "root_server": "http://bmlt3x:8001/main_server/",
  "service_body_1":"Mid-Hudson Area Service,1009,1046,ABCD Region",
  "custom_query": "",
  "custom_css": "",
  "meeting_data_template": "{{#isTemporarilyClosed this}}\r\n    <div class='temporarilyClosed'><span class='glyphicon glyphicon-flag'></span> {{temporarilyClosed this}}</div>\r\n{{/isTemporarilyClosed}}\r\n<div class='meeting-name'>{{this.meeting_name}}</div>\r\n<div class='location-text'>{{this.location_text}}</div>\r\n<div class='meeting-address'>{{this.formatted_address}}</div>\r\n<div class='location-information'>{{this.formatted_location_info}}</div>\r\n{{#if this.virtual_meeting_additional_info}}\r\n    <div class='meeting-additional-info'>{{this.virtual_meeting_additional_info}}</div>\r\n{{/if}}",
  "metadata_template": "{{#isVirtualOrHybrid this}}\r\n    {{#isHybrid this}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud-upload'></span> {{meetsHybrid this}}</div>\r\n    {{else}}\r\n        <div class='meetsVirtually'><span class='glyphicon glyphicon-cloud'></span> {{meetsVirtually this}}</div>\r\n    {{/isHybrid}}\r\n    {{#if this.virtual_meeting_link}}\r\n        <div><span class='glyphicon glyphicon-globe'></span> {{webLinkify this.virtual_meeting_link}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.virtual_meeting_link}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n    {{#if this.phone_meeting_number}}\r\n        <div><span class='glyphicon glyphicon-earphone'></span> {{phoneLinkify this.phone_meeting_number}}</div>\r\n        {{#if this.show_qrcode}}\r\n            <div class='qrcode'>{{qrCode this.phone_meeting_number}}</div>\r\n        {{/if}}\r\n    {{/if}}\r\n{{/isVirtualOrHybrid}}\r\n{{#isNotTemporarilyClosed this}}\r\n    {{#unless (hasFormats 'VM' this)}}\r\n        <div>\r\n            <a id='map-button' class='btn btn-primary btn-xs'\r\n                href='https://www.google.com/maps/search/?api=1&query={{this.latitude}},{{this.longitude}}&q={{this.latitude}},{{this.longitude}}'\r\n                target='_blank' rel='noopener noreferrer'>\r\n                <span class='glyphicon glyphicon-map-marker'></span> {{this.map_word}}</a>\r\n        </div>\r\n        <div class='geo hide'>{{this.latitude}},{{this.longitude}}</div>\r\n    {{/unless}}\r\n{{/isNotTemporarilyClosed}}",
  "theme": "",
  "recurse_service_bodies": "0",
  "extra_meetings": [],
  "extra_meetings_enabled": "0",
  "google_api_key": ""
}
EOF
END
cat << END >> /var/www/html/crouton2x.php
<?php exec('/bin/sh /var/www/html/crouton2x.sh');
END
cat << END >> /var/www/html/crouton3x.php
<?php exec('/bin/sh /var/www/html/crouton3x.sh');
END

apache2-foreground


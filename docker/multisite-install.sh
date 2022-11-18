#!/bin/bash

if [[ "$WORDPRESS_MULTISITE_INSTALL" -eq "true" ]]
then
    cp -f /var/www/html/htaccess.multisite /var/www/html/.htaccess
    wp db reset --yes
    wp core multisite-install --url="http://$WORDPRESS_MULTISITE_HOST:$WORDPRESS_MULTISITE_PORT" --title="hi" --admin_user=wpuser --admin_password=wppass --admin_email=crap@crap.com --skip-email --path=/var/www/html
fi

apache2-foreground

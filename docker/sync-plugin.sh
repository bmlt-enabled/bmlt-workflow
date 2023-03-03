#!/bin/sh

WORDPRESS_PATH=$1
if [ -z "$WORDPRESS_PATH" ]
then
    WORDPRESS_PATH=/var/www/html
fi

PLUGIN_PATH=$WORDPRESS_PATH/wp-content/plugins/bmlt-workflow

FILES='bmlt-workflow.php config.php uninstall.php admin assets css images lang js public src templates thirdparty'
for i in $FILES
do
cp -R /plugin/$i $PLUGIN_PATH
done

sed -i "s/define('BMLTWF_DEBUG', false);/define('BMLTWF_DEBUG', true);/g" $PLUGIN_PATH/config.php

#!/bin/sh

WORDPRESS_PATH=$1
if [ -z "$WORDPRESS_PATH" ]
then
    WORDPRESS_PATH=/var/www/html
fi

PLUGIN_PATH=$WORDPRESS_PATH/wp-content/plugins/bmlt-workflow

FILES='bmlt-workflow.php config.php uninstall.php admin assets css images js public src templates thirdparty'
for i in $FILES
do
cp -R /plugin/$i $PLUGIN_PATH
done
#!/bin/bash
export sitelocalpath=/var/www/html

# Drop existing tables first
mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME -e "
DROP TABLE IF EXISTS wp_bmltwf_service_bodies_access;
DROP TABLE IF EXISTS wp_bmltwf_submissions;
DROP TABLE IF EXISTS wp_bmltwf_service_bodies;
"

for sql_file in /sql-import/*.sql; do
    if [ -f "$sql_file" ]; then
        mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME < "$sql_file"
        echo "Imported $sql_file"
    fi
done

wp option --path=$sitelocalpath update 'bmltwf_db_version' '0.4.0'

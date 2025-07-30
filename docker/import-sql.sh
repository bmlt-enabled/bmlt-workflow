#!/bin/bash
export sitelocalpath=/var/www/html

# Drop existing tables first
mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME -e "
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';
DROP TABLE IF EXISTS wp_bmltwf_correspondence;
DROP TABLE IF EXISTS wp_bmltwf_service_bodies_access;
DROP TABLE IF EXISTS wp_bmltwf_submissions;
DROP TABLE IF EXISTS wp_bmltwf_service_bodies;
DROP TABLE IF EXISTS wp_bmltwf_debug_log;
SET FOREIGN_KEY_CHECKS = 1;
SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
"

for sql_file in /sql-import/*.sql; do
    if [ -f "$sql_file" ]; then
        mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME < "$sql_file"
        echo "Imported $sql_file"
    fi
done

wp option --path=$sitelocalpath update 'bmltwf_db_version' '0.4.0'
wp plugin deactivate --path=$sitelocalpath "bmlt-workflow"
wp plugin activate --path=$sitelocalpath "bmlt-workflow"

mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME -e "
SET sql_mode = '';
UPDATE wp_bmltwf_submissions SET submission_time = '1970-01-01 00:00:01' WHERE submission_time = '0000-00-00 00:00:00';
UPDATE wp_bmltwf_submissions SET change_time = NULL WHERE change_time = '0000-00-00 00:00:00';
SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
ALTER TABLE wp_bmltwf_submissions 
MODIFY COLUMN submission_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN change_time datetime NULL DEFAULT NULL,
MODIFY COLUMN change_id bigint(20) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 1402;
"

mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME -e "
ALTER TABLE wp_bmltwf_submissions 
ADD CONSTRAINT fk_submissions_servicebody 
FOREIGN KEY (serviceBodyId) 
REFERENCES wp_bmltwf_service_bodies(serviceBodyId);
"

mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME -e "
ALTER TABLE wp_bmltwf_service_bodies_access
ADD CONSTRAINT fk_submissions_access_servicebody 
FOREIGN KEY (serviceBodyId) 
REFERENCES wp_bmltwf_service_bodies(serviceBodyId);
"

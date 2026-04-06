#!/bin/sh
set -e

mysql -u root --password=$MYSQL_ROOT_PASSWORD <<SQL
CREATE DATABASE IF NOT EXISTS \`wordpress-php8-singlesite\`;
CREATE DATABASE IF NOT EXISTS \`wordpress-php8-multisitesingle\`;
CREATE DATABASE IF NOT EXISTS \`wordpress-php8-multinetwork\`;
CREATE DATABASE IF NOT EXISTS \`wordpress-php8-dbupgrade\`;
GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
#!/bin/sh

mysql -u root --password=$MYSQL_ROOT_PASSWORD -e "grant all on *.* to ${MYSQL_USER}"
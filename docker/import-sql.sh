#!/bin/bash
for sql_file in /sql-import/*.sql; do
    if [ -f "$sql_file" ]; then
        mysql -h$WORDPRESS_DB_HOST -u$WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD $WORDPRESS_DB_NAME < "$sql_file"
        echo "Imported $sql_file"
    fi
done

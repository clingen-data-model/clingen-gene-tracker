#!/bin/bash
set -eo pipefail
shopt -s nullglob

# source /usr/local/bin/docker-entrypoint.sh

# docker_process_sql --dont-use-mysql-root-password --database=mysql <<-EOSQL
#     mysql_note "Creating ${DB_DATABASE} db"
#     CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    
#     mysql_note "Creating testing db"
#     CREATE DATABASE IF NOT EXISTS testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

#     mysql_note "Grant all privs to ${DB_USERNAME}
#     GRANT ALL ON *.* TO '${DB_USERNAME}'@'%';
#     GRANT ALL ON testing.* TO '${DB_USERNAME}'@'%';
# EOSQL
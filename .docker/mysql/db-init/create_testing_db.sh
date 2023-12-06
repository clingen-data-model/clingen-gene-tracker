#!/bin/bash
set -eo pipefail
shopt -s nullglob

if [[ ! -v DB_DATABASE_TEST ]]; then
  echo '$DB_DATABASE_TEST is not defined, skipping testing db creation'
else

  source /usr/local/bin/docker-entrypoint.sh

  docker_process_sql --database=mysql <<EOSQL
    CREATE DATABASE IF NOT EXISTS ${DB_DATABASE_TEST};
    GRANT ALL ON ${DB_DATABASE_TEST}.* TO '${DB_USERNAME:-gene_tracker}'@'%';
EOSQL
fi

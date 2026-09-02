#!/bin/bash
set -eo pipefail
shopt -s nullglob

if [[ -v DB_DATABASE_TEST || -v DB_DATABASE_E2E ]]; then
  source /usr/local/bin/docker-entrypoint.sh
fi

if [[ ! -v DB_DATABASE_TEST ]]; then
  echo '$DB_DATABASE_TEST is not defined, skipping testing db creation'
else
  docker_process_sql --database=mysql <<EOSQL
    CREATE DATABASE IF NOT EXISTS ${DB_DATABASE_TEST};
    GRANT ALL ON ${DB_DATABASE_TEST}.* TO '${DB_USERNAME:-gene_tracker}'@'%';
EOSQL
fi

if [[ ! -v DB_DATABASE_E2E ]]; then
  echo '$DB_DATABASE_E2E is not defined, skipping e2e db creation'
else
  docker_process_sql --database=mysql <<EOSQL
    CREATE DATABASE IF NOT EXISTS ${DB_DATABASE_E2E};
    GRANT ALL ON ${DB_DATABASE_E2E}.* TO '${DB_USERNAME:-gene_tracker}'@'%';
EOSQL
fi

#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

for WAITSECONDS in 2 2 2 4 4 4 8 8 8 16 16 16
do
  php artisan healthcheck:db && exit 0
  echo "Not yet connected to db... waiting $WAITSECONDS"
  sleep $WAITSECONDS
done

echo "UNABLE TO CONNECT TO DB!"
exit 1

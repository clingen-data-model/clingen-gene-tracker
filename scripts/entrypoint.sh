#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

env=${CONTAINER_ENV:-production}

cd /srv/app

# by default, do not install dev deps, but install them of COMPOSER_NO_DEV is set to 0
export COMPOSER_NO_DEV=${COMPOSER_NO_DEV:-1}

if [[ -v APP_DO_INIT || ! -d vendor ]]; then
    echo "Running composer install..."
    composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-suggest
    composer dump-autoload
fi

echo "Running migrations..."
php artisan migrate --force --no-interaction

echo "Making passport keys (if they do not already exist)"
php artisan passport:keys || echo "... keys were probably already there"

# make a new APP_KEY if not in environment and if the one in .env is not properly formatted
if [[ ${APP_KEY:-invalid} != base64* ]]; then
    touch .env # make sure we have an .env file soe key:generate doesn't complain
    grep APP_KEY=base64 .env >/dev/null || php artisan key:generate -q
fi

if [[ $env != "local" ]]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan clear-compiled
    php artisan notify:deployed
fi

# the old (pre-nginx) command:
#php artisan serve -vvv --host 0.0.0.0 --port "${APP_PORT:-8013}"

php-fpm${PHP_VERSION} -F -O


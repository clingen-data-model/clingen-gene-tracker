#!/usr/bin/env bash

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

if [ "$env" != "local" ]; then
    echo "Caching configuration..."
    (cd /srv/app && php artisan config:cache && php artisan route:cache && php artisan view:cache)
fi

if [ "$role" = "app" ]; then

    exec apache2-foreground

elif [ "$role" = "queue" ]; then

    # echo "role: queue"
    # echo "SESSION_DRIVER: $SESSION_DRIVER"
    # echo "CACHE_DRIVER: $CACHE_DRIVER"
    # echo "QUEUE_CONNECTION: $QUEUE_CONNECTION..."
    php /srv/app/artisan queue:work --verbose --tries=3 --timeout=90

elif [ "$role" = "scheduler" ]; then

    while [ true ]
    do
      php /srv/app/artisan schedule:run --verbose --no-interaction &
      sleep 60
    done

elif [ "$role" = "artisan"]; then
    echo "role: artisan"

else
    echo "Could not match the container role \"$role\""
    exit 1
fi
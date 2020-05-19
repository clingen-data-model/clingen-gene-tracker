#!/bin/bash

php artisan clear-compiled
php artisan migrate --force --no-interaction
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
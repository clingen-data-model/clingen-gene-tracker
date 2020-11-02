#!/bin/bash

php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan clear-compiled
php artisan migrate --force --no-interaction
php artisan db:seed --class=RolesAndPermissionsSeeder --force --no-interaction
php artisan cache:clear
php artisan notify:deployed
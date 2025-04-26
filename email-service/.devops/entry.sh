#!/bin/sh

cd /var/www/

composer dump-autoload
php artisan migrate --force
php artisan cache:clear
php artisan route:cache
php artisan config:clear

#!/bin/sh

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! nc -z db 3306; do
    sleep 1
done

cd /var/www

# Ensure proper permissions
chown -R www:www /var/www/storage
chmod -R 775 /var/www/storage

# Clear cache and optimize
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run migrations
php artisan migrate --force

# Start PHP-FPM
php-fpm
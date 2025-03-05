#!/bin/sh

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! nc -z db 3306; do
    sleep 1
done

cd /var/www

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    composer install
fi

# Run migrations
php artisan migrate --force

# Start PHP-FPM
php-fpm
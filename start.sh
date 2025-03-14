#!/bin/sh

# Iniciar PHP-FPM en segundo plano
php-fpm82 --daemonize

# Iniciar el servicio de Puppeteer en segundo plano
cd /app/puppeteer && node server.js &

# Iniciar Nginx en primer plano
nginx -g "daemon off;"

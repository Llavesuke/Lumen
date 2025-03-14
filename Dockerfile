# Stage 1: Build Frontend
FROM node:18-alpine as frontend-builder
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# Stage 2: Build Backend
FROM composer:2.5 as backend-builder
WORKDIR /app/backend
COPY backend/composer.* ./
RUN composer install --no-dev --no-scripts --no-autoloader
COPY backend/ ./
RUN composer dump-autoload --optimize

# Stage 3: Build Puppeteer Service
FROM node:18-alpine as puppeteer-builder
WORKDIR /app/puppeteer
COPY backend/puppeteer-service/package*.json ./
RUN npm install
COPY backend/puppeteer-service/ ./

# Stage 4: Final Image
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    chromium \
    chromium-chromedriver \
    mysql \
    mysql-client \
    mariadb-connector-c

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy built assets from previous stages
COPY --from=frontend-builder /app/frontend/dist /app/frontend/dist
COPY --from=backend-builder /app/backend /app/backend
COPY --from=puppeteer-builder /app/puppeteer /app/puppeteer

# Copy configuration files
COPY frontend/nginx.conf /etc/nginx/http.d/default.conf
COPY backend/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set up MySQL
RUN mkdir -p /run/mysqld && \
    chown -R mysql:mysql /run/mysqld && \
    mysql_install_db --user=mysql --datadir=/var/lib/mysql

# Set working directory
WORKDIR /app

# Set environment variables
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser \
    MYSQL_DATABASE=laravel \
    MYSQL_ROOT_PASSWORD=root \
    MYSQL_PASSWORD=password \
    MYSQL_USER=laravel

# Expose ports
EXPOSE 80 3306 3000 8080

# Start services using supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
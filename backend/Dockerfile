FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    netcat-traditional

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Create necessary directories
RUN mkdir -p /var/www/storage/framework/{cache,sessions,views} \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/vendor

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application code
COPY --chown=www:www . /var/www

# Generate autoloader and run scripts
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www:www /var/www
RUN chmod -R 775 /var/www/storage

# Switch to www user
USER www

# Install dependencies
RUN composer install --optimize-autoloader

# Copy existing application directory contents and set permissions
COPY --chown=www:www . /var/www

# Generate optimized autoloader and run scripts
RUN composer dump-autoload --optimize

# Install dependencies
RUN composer install --optimize-autoloader

# Copy existing application directory contents and set permissions
COPY --chown=www:www . /var/www

# Generate optimized autoloader and run scripts
RUN composer dump-autoload --optimize

# Set proper permissions
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/vendor

# Change current user to www
USER www

# Expose port 9000
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["entrypoint.sh"]
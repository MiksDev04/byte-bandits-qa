# Use official PHP with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install Composer dependencies (if composer.lock exists)
RUN composer install --no-cache --no-dev --optimize-autoloader --no-interaction \
    && composer dump-autoload --optimize --no-dev

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache mod_rewrite (for clean URLs)
RUN a2enmod rewrite

# Expose port
EXPOSE 80
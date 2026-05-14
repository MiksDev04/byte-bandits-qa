FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    curl unzip libzip-dev \
    && docker-php-ext-install zip mysqli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Copy Aiven CA cert to a known path
COPY ca.pem /etc/ssl/aiven/ca.pem
RUN chmod 644 /etc/ssl/aiven/ca.pem

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
RUN a2enmod rewrite

EXPOSE 80
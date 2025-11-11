# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files into container
COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite (for pretty URLs, optional)
RUN a2enmod rewrite

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all project files into container
COPY . /var/www/html

# Give Apache proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Expose port 80
EXPOSE 80

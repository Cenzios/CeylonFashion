FROM php:8.1-apache

# Enable Apache mod_rewrite (Laravel / WordPress / custom routing)
RUN a2enmod rewrite

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Give Apache permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Expose web port
EXPOSE 80

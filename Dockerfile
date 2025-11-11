# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install mysqli and pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install zip extension (if needed)
RUN apt-get update && apt-get install -y libzip-dev zip \
    && docker-php-ext-install zip

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY ./app /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s CMD curl -f http://localhost/ || exit 1

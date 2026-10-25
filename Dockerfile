FROM php:8.5-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable mod_rewrite for Apache (Nette routing)
RUN a2enmod rewrite

# Set DocumentRoot to /var/www/html/www (Nette structure)
ENV APACHE_DOCUMENT_ROOT /var/www/html/www
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# PHP settings (timezone, limits)
RUN echo "date.timezone = Europe/Prague" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/timezone.ini

# Working directory
WORKDIR /var/www/html

# Permissions for temp and log directories
RUN mkdir -p /var/www/html/temp /var/www/html/log \
    && chown -R www-data:www-data /var/www/html

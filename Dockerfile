# ============================================
# BASE IMAGE - Apache + PHP 8.4
# ============================================
FROM php:8.4-apache AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache to serve from /var/www/html (like XAMPP)
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy project to /var/www/html/assistpro_kardex_fc (like XAMPP structure)
RUN mkdir -p assistpro_kardex_fc
COPY . assistpro_kardex_fc/

# Create storage directories and set permissions
RUN mkdir -p assistpro_kardex_fc/storage/framework/views \
             assistpro_kardex_fc/storage/logs \
             assistpro_kardex_fc/uploads \
    && chown -R www-data:www-data assistpro_kardex_fc \
    && chmod -R 755 assistpro_kardex_fc/storage \
    && chmod -R 755 assistpro_kardex_fc/uploads

# ============================================
# DEVELOPMENT IMAGE
# ============================================
FROM base AS development

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Development PHP config
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/dev.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/dev.ini

# Install dependencies
WORKDIR /var/www/html/assistpro_kardex_fc
RUN composer install --no-interaction --prefer-dist

# Copy db.php template
COPY docker/db.php.docker app/db.php
RUN chown www-data:www-data app/db.php

# Copy redirect index.php to web root
COPY docker/index.php /var/www/html/index.php
RUN chown www-data:www-data /var/www/html/index.php

EXPOSE 80

# ============================================
# PRODUCTION IMAGE  
# ============================================
FROM base AS production

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Production PHP config
COPY docker/php-production.ini /usr/local/etc/php/conf.d/production.ini

# Install dependencies (no dev)
WORKDIR /var/www/html/assistpro_kardex_fc
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy db.php for production
COPY docker/db.php.docker app/db.php
RUN chown www-data:www-data app/db.php

# Copy redirect index.php to web root
COPY docker/index.php /var/www/html/index.php
RUN chown www-data:www-data /var/www/html/index.php

EXPOSE 80

FROM php:8.4-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    intl \
    zip \
    exif

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Create system user to run Composer and Symfony commands
RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -h /home/symfony -s /bin/sh -D symfony

# Set permissions
RUN chown -R symfony:symfony /var/www

USER symfony

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

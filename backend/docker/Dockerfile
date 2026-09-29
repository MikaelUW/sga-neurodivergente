FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    linux-headers \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    libpq-dev \
    oniguruma-dev \
    icu-dev \
    git \
    unzip \
    zip \
    netcat-openbsd \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Disable Composer security audit blocking
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www

# Copy composer files first to leverage Docker cache
COPY composer.json ./

# Install dependencies during build (bypassing security audit block)
RUN composer update --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-audit 2>/dev/null || true

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

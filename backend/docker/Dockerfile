# =====================================================================
#  Dockerfile - SGAN API (multi-stage, build reproduzível)
# =====================================================================
# Imagens com tag fixa (pinned). Para máxima reprodutibilidade,
# pode-se trocar por digest @sha256:... quando desejado.

# ---------- Stage 1: dependências (vendor) ----------
FROM php:8.3.14-fpm-alpine AS vendor

RUN apk add --no-cache libzip-dev libpq-dev icu-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath intl zip \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www

# Copia apenas os manifestos primeiro (aproveita cache do Docker).
# Usa composer.lock -> build reproduzível (install, NÃO update).
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-dev \
    --no-scripts --no-autoloader --no-progress

# ---------- Stage 2: runtime ----------
FROM php:8.3.14-fpm-alpine AS runtime

RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    libpq-dev \
    oniguruma-dev \
    icu-dev \
    netcat-openbsd \
    postgresql-client \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd intl zip \
    && apk del $PHPIZE_DEPS

# pdo_sqlite é usado pela suíte de testes (DB em memória)
RUN apk add --no-cache sqlite-libs sqlite-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_sqlite \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www

# Traz o vendor já resolvido do stage anterior
COPY --from=vendor /var/www/vendor ./vendor

# Copia o código da aplicação
COPY . .

# Finaliza o autoloader otimizado agora que o código está presente
RUN composer dump-autoload --optimize --no-dev

# Usuário sem privilégios para o php-fpm (segurança)
RUN addgroup -g 1000 -S sgan && adduser -u 1000 -S sgan -G sgan \
    && mkdir -p storage/framework/sessions storage/framework/views \
       storage/framework/cache storage/app/public/laudos storage/app/laudos_privados \
       bootstrap/cache \
    && chown -R sgan:sgan /var/www

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER sgan

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

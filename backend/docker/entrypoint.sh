#!/bin/bash

echo "=== SGAN Entrypoint ==="

# Garantir que vendor existe (pode ter sido montado por volume)
if [ ! -f "vendor/autoload.php" ]; then
    echo "Vendor nao encontrado, instalando dependencias..."
    COMPOSER_ALLOW_SUPERUSER=1 composer update --no-interaction --prefer-dist --no-audit 2>/dev/null || true
fi

if [ ! -f ".env" ]; then
    echo "Copiando .env.example para .env..."
    cp .env.example .env
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/app/public/laudos storage/app/laudos_privados
mkdir -p bootstrap/cache
chmod -R 777 storage bootstrap/cache

echo "Aguardando Banco de Dados PostgreSQL..."
until nc -z db 5432; do
  sleep 1
done
echo "PostgreSQL pronto!"

if [ -f "vendor/autoload.php" ]; then
    echo "Executando artisan commands..."
    php artisan key:generate --force 2>/dev/null || true
    php artisan storage:link --force 2>/dev/null || true
    php artisan migrate --force 2>/dev/null || true
    php artisan db:seed --force 2>/dev/null || true
    echo "Setup completo!"
else
    echo "AVISO: vendor/autoload.php nao encontrado. Artisan nao executado."
fi

exec "$@"

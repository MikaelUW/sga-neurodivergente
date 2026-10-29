#!/bin/bash
set -e

echo "=== SGAN Entrypoint ==="

# -------------------------------------------------------------------
# .env: cria a partir do exemplo apenas se não existir.
# Em produção o .env (ou as variáveis) devem ser injetados pelo
# orquestrador / secret manager — não dependa deste fallback.
# -------------------------------------------------------------------
if [ ! -f ".env" ]; then
    echo "[entrypoint] .env ausente — copiando de .env.example"
    cp .env.example .env
fi

# Em desenvolvimento o código é montado por bind-mount, o que pode
# esconder o vendor/ da imagem. A imagem traz vendor SEM dev-deps.
# Fora de produção precisamos das dev-deps (phpunit, collision), então
# instalamos se faltarem. Em produção, vendor sem dev-deps já basta.
IS_PROD="$([ "$(grep -E '^APP_ENV=' .env | cut -d '=' -f2-)" = "production" ] && echo yes || echo no)"

need_install="no"
if [ ! -f "vendor/autoload.php" ]; then
    need_install="yes"
elif [ "$IS_PROD" = "no" ] && [ ! -d "vendor/nunomaduro/collision" ]; then
    # vendor existe mas sem dev-deps num ambiente de desenvolvimento
    need_install="yes"
fi

if [ "$need_install" = "yes" ]; then
    echo "[entrypoint] Instalando dependências (composer install, prod=$IS_PROD)"
    if [ "$IS_PROD" = "yes" ]; then
        composer install --no-interaction --prefer-dist --no-progress --no-dev
    else
        composer install --no-interaction --prefer-dist --no-progress
    fi
fi

# Garante os diretórios de storage (sem 777 — ownership já é do usuário sgan)
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
         storage/app/public/laudos storage/app/laudos_privados bootstrap/cache

# -------------------------------------------------------------------
# APP_KEY: deve estar definida no arquivo .env (lido pelo Laravel).
# Não injetamos o .env como variáveis de ambiente do SO (ver compose),
# justamente para evitar precedência indevida. Validamos lendo o arquivo.
# -------------------------------------------------------------------
APP_KEY_VALUE="$(grep -E '^APP_KEY=' .env | cut -d '=' -f2-)"
APP_ENV_VALUE="$(grep -E '^APP_ENV=' .env | cut -d '=' -f2- || echo local)"

if [ -z "$APP_KEY_VALUE" ]; then
    echo "[entrypoint] ERRO: APP_KEY vazia no .env." >&2
    echo "              Gere e grave no .env antes de subir:" >&2
    echo "              docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi
echo "[entrypoint] APP_KEY presente."

# -------------------------------------------------------------------
# Espera o banco com timeout (não trava para sempre).
# -------------------------------------------------------------------
DB_HOST_VALUE="${DB_HOST:-db}"
DB_PORT_VALUE="${DB_PORT:-5432}"
echo "[entrypoint] Aguardando PostgreSQL em ${DB_HOST_VALUE}:${DB_PORT_VALUE}..."
ATTEMPTS=0
MAX_ATTEMPTS=30
until nc -z "$DB_HOST_VALUE" "$DB_PORT_VALUE"; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
        echo "[entrypoint] ERRO: banco não respondeu após ${MAX_ATTEMPTS}s." >&2
        exit 1
    fi
    sleep 1
done
echo "[entrypoint] PostgreSQL pronto."

php artisan storage:link --force || true

# -------------------------------------------------------------------
# Migrations: sempre (idempotentes com --force).
# Seed: SOMENTE fora de produção (dados de demonstração).
# -------------------------------------------------------------------
php artisan migrate --force

if [ "$APP_ENV_VALUE" != "production" ]; then
    echo "[entrypoint] Ambiente $APP_ENV_VALUE — rodando seeders de demonstração."
    php artisan db:seed --force || true
else
    echo "[entrypoint] Produção — seeders de demonstração NÃO executados."
fi

# Cache de config/rotas em produção (performance)
if [ "$APP_ENV_VALUE" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
fi

echo "[entrypoint] Setup completo."
exec "$@"

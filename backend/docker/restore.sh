#!/bin/bash
# =====================================================================
#  Restore do banco PostgreSQL do SGAN a partir de um dump do backup.sh.
#
#  IMPORTANTE SOBRE A APP_KEY:
#  Os dados sensíveis são cifrados com a APP_KEY da aplicação. Restaurar
#  o banco NÃO basta — você precisa da MESMA APP_KEY (e BLIND_INDEX_KEY)
#  usada quando os dados foram gravados, senão os campos cifrados ficam
#  ilegíveis. Guarde as chaves junto do backup, em cofre/secret manager.
#
#  Uso (host) — restaura o dump mais recente:
#    docker compose -f docker-compose.yml -f docker-compose.prod.yml \
#      exec db /backups/restore.sh
#
#  Ou um arquivo específico:
#    ... exec db /backups/restore.sh /backups/sgan_sgan_db_YYYYMMDD_HHMMSS.dump
#
#  Dica de go-live: teste o restore num banco descartável ANTES de
#  confiar no backup (ver --create-test abaixo).
# =====================================================================
set -euo pipefail

BACKUP_DIR="/backups"
DB_NAME="${POSTGRES_DB:-sgan_db}"
DB_USER="${POSTGRES_USER:-sgan_user}"

FILE="${1:-}"
if [ -z "$FILE" ]; then
    FILE="$(ls -1t "${BACKUP_DIR}"/sgan_*.dump 2>/dev/null | head -n1 || true)"
fi

if [ -z "$FILE" ] || [ ! -f "$FILE" ]; then
    echo "[restore] ERRO: nenhum arquivo de dump encontrado. Informe o caminho." >&2
    exit 1
fi

# Verifica integridade se houver checksum.
if [ -f "${FILE}.sha256" ]; then
    echo "[restore] Verificando checksum..."
    (cd "$(dirname "$FILE")" && sha256sum -c "$(basename "$FILE").sha256")
fi

echo "[restore] Restaurando '${FILE}' em '${DB_NAME}'."
echo "[restore] Isso vai SOBRESCREVER objetos existentes (--clean --if-exists)."

pg_restore -U "$DB_USER" -d "$DB_NAME" --clean --if-exists --no-owner "$FILE"

echo "[restore] Concluído."

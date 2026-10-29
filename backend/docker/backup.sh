#!/bin/bash
# =====================================================================
#  Backup do banco PostgreSQL do SGAN.
#
#  Executa um pg_dump comprimido (formato custom) para /backups, com
#  retenção configurável. Pensado para rodar dentro da rede do compose,
#  a partir do serviço db (que tem o cliente pg).
#
#  Uso (host):
#    docker compose -f docker-compose.yml -f docker-compose.prod.yml \
#      exec db /backups/backup.sh
#
#  Agende via cron no host chamando o comando acima, ex. diário 02:00.
#
#  Variáveis (herdadas do container db):
#    POSTGRES_DB, POSTGRES_USER  — nome do banco/usuário
#    BACKUP_RETENTION_DAYS       — dias de retenção (padrão 14)
# =====================================================================
set -euo pipefail

BACKUP_DIR="/backups"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
DB_NAME="${POSTGRES_DB:-sgan_db}"
DB_USER="${POSTGRES_USER:-sgan_user}"
TS="$(date +%Y%m%d_%H%M%S)"
OUT="${BACKUP_DIR}/sgan_${DB_NAME}_${TS}.dump"

mkdir -p "$BACKUP_DIR"

echo "[backup] Iniciando pg_dump de '${DB_NAME}' -> ${OUT}"
# Formato custom (-Fc): comprimido e restaurável com pg_restore.
pg_dump -U "$DB_USER" -d "$DB_NAME" -Fc -f "$OUT"

# Checksum para verificação de integridade.
sha256sum "$OUT" > "${OUT}.sha256"

echo "[backup] Concluído: $(du -h "$OUT" | cut -f1) — ${OUT}"

# Retenção: remove dumps (e checksums) mais antigos que N dias.
echo "[backup] Aplicando retenção de ${RETENTION_DAYS} dias..."
find "$BACKUP_DIR" -name 'sgan_*.dump' -type f -mtime "+${RETENTION_DAYS}" -print -delete
find "$BACKUP_DIR" -name 'sgan_*.dump.sha256' -type f -mtime "+${RETENTION_DAYS}" -print -delete

echo "[backup] OK."

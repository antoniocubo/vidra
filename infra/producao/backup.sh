#!/bin/sh
# Backup diário: pg_dump em formato custom, 14 dias local e cópia no bucket S3 de backup.
# Roda dentro do container "backup" (ver docker-compose.yml), que chama este script todo dia às 03:00 (Brasília).
set -eu
DIR=/backups
ARQ="$DIR/vidra-$(date -u +%Y%m%d-%H%M%S).dump"
export PGPASSWORD="$POSTGRES_PASSWORD"

pg_dump -h postgres -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc -f "$ARQ"
find "$DIR" -name 'vidra-*.dump' -mtime +14 -delete

if [ -n "${BACKUP_S3_BUCKET:-}" ]; then
  aws s3 cp "$ARQ" "s3://$BACKUP_S3_BUCKET/$(basename "$ARQ")" --storage-class STANDARD_IA --only-show-errors
fi
echo "backup ok: $ARQ"

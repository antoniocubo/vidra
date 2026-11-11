#!/bin/sh
# Dorme até 06:00 UTC (03:00 em Brasília) e roda o backup; repete todo dia. Uma falha não derruba o loop.
while true; do
  agora=$(date -u +%s)
  alvo=$(date -u -d "06:00" +%s 2>/dev/null || date -u -d "today 06:00" +%s)
  [ "$alvo" -le "$agora" ] && alvo=$((alvo + 86400))
  sleep $((alvo - agora))
  /backup.sh || echo "backup FALHOU" >&2
done

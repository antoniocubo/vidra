#!/bin/sh
# Deploy: rodar da raiz do repositório, no servidor. Uso: infra/producao/deploy.sh
set -eu
cd "$(dirname "$0")/../.."
DC="docker compose -f infra/producao/docker-compose.yml --env-file platform/.env"

git pull --ff-only
$DC build app

# Banco e dependências no ar antes das migrations
$DC up -d postgres redis gotenberg

# Migrations com o dono das tabelas (vidra), nunca com vidra_app
$DC run --rm app php artisan migrate --database=pgsql_sistema --force

# Troca os containers da aplicação; o caddy fica de pé, sem derrubar o TLS
$DC exec -T horizon php artisan horizon:terminate || true
$DC up -d --no-deps app horizon scheduler
$DC up -d caddy backup

# Caches depois de subir, dentro do container novo
$DC exec -T app sh -c 'php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache'
$DC exec -T horizon php artisan horizon:terminate || true # recarrega o Horizon com o cache novo
echo "deploy ok"

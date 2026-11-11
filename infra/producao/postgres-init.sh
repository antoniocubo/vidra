#!/bin/sh
# Roda uma vez, quando o volume do Postgres é criado. Reaproveita o SQL de desenvolvimento,
# trocando a senha do vidra_app pela do ambiente e sem o banco vidra_testes.
set -eu
sed "s/password 'vidra_app'/password '${VIDRA_APP_PASSWORD}'/" /sql/01-vidra-app.sql \
  | psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB"
psql -U "$POSTGRES_USER" -d postgres -c "drop database if exists vidra_testes"

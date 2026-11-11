#!/bin/sh
# Roda uma vez, quando o volume do Postgres é criado.
# Papéis: postgres (superusuário, só administração; a aplicação nunca tem essa senha),
# vidra (dono das tabelas, roda as migrations e a conexão de sistema: NOSUPERUSER, com
# BYPASSRLS porque as tabelas usam FORCE ROW LEVEL SECURITY) e vidra_app (a aplicação,
# sujeita ao RLS). As senhas entram como variáveis do psql (:'x'), sem montar SQL na mão.
set -eu
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -v vidra_pw="$VIDRA_PASSWORD" <<'SQL'
create role vidra login nosuperuser nocreaterole bypassrls password :'vidra_pw';
alter database vidra owner to vidra;
alter schema public owner to vidra;
SQL
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /sql/01-vidra-app.sql
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -v app_pw="$VIDRA_APP_PASSWORD" <<'SQL'
alter role vidra_app password :'app_pw';
SQL
psql -U "$POSTGRES_USER" -d postgres -c "drop database if exists vidra_testes"

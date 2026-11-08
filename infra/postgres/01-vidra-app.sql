-- Usuário da aplicação: sem superusuário e sem BYPASSRLS, então obedece às
-- policies de Row Level Security. As tabelas são do usuário vidra (migrations).
-- Também cria o banco vidra_testes, usado por `composer test:postgres`.
-- Roda sozinho quando o volume é criado. Num volume que já existe:
--   docker compose -f infra/docker-compose.yml exec -T postgres psql -U vidra -d vidra < infra/postgres/01-vidra-app.sql
do $$
begin
    if not exists (select from pg_roles where rolname = 'vidra_app') then
        create role vidra_app login password 'vidra_app' nosuperuser nobypassrls;
    end if;
end
$$;

select 'create database vidra_testes owner vidra'
where not exists (select from pg_database where datname = 'vidra_testes')\gexec

\connect vidra
grant usage on schema public to vidra_app;
grant select, insert, update, delete on all tables in schema public to vidra_app;
grant usage, select on all sequences in schema public to vidra_app;
alter default privileges for role vidra in schema public grant select, insert, update, delete on tables to vidra_app;
alter default privileges for role vidra in schema public grant usage, select on sequences to vidra_app;

\connect vidra_testes
grant usage on schema public to vidra_app;
grant select, insert, update, delete on all tables in schema public to vidra_app;
grant usage, select on all sequences in schema public to vidra_app;
alter default privileges for role vidra in schema public grant select, insert, update, delete on tables to vidra_app;
alter default privileges for role vidra in schema public grant usage, select on sequences to vidra_app;

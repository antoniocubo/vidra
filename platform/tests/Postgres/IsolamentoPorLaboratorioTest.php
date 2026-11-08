<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Shared\Infrastructure\Tenancy\Rls;

/*
| Testes da segunda defesa (RLS). Só rodam no Postgres: composer test:postgres.
| A aplicação entra como vidra_app (sujeita ao RLS); a preparação usa a pgsql_sistema.
*/

/** Tabelas com laboratorio_id que, de propósito, não ficam sob RLS. */
const TABELAS_SEM_RLS = [
    'laboratorio_usuario', // mapa de acessos: lido antes de existir um laboratório atual
];

beforeEach(function (): void {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Precisa do Postgres: composer test:postgres');
    }

    Artisan::call('migrate:fresh', ['--database' => 'pgsql_sistema']);
    $sistema = DB::connection('pgsql_sistema');
    $sistema->statement('create table rls_teste (id serial primary key, laboratorio_id uuid not null, valor text not null)');

    DB::setDefaultConnection('pgsql_sistema');
    Rls::isolarPorLaboratorio('rls_teste');
    DB::setDefaultConnection('pgsql');

    $this->alfa = Laboratorios::criar('Laboratório Alfa');
    $this->beta = Laboratorios::criar('Laboratório Beta');
    $sistema->table('rls_teste')->insert([
        ['laboratorio_id' => $this->alfa, 'valor' => 'do alfa'],
        ['laboratorio_id' => $this->beta, 'valor' => 'do beta'],
    ]);
    $this->contexto = app(ContextoLaboratorio::class);
});

afterEach(function (): void {
    if (config('database.default') === 'pgsql') {
        app(ContextoLaboratorio::class)->limpar();
        DB::connection('pgsql_sistema')->statement('drop table if exists rls_teste');
    }
});

it('não mostra nenhuma linha sem laboratório definido', function (): void {
    expect(DB::table('rls_teste')->count())->toBe(0);
});

it('mostra só as linhas do laboratório atual, mesmo em SQL cru', function (): void {
    $this->contexto->definir($this->alfa);

    expect(DB::select('select valor from rls_teste'))->toHaveCount(1)
        ->and(DB::table('rls_teste')->value('valor'))->toBe('do alfa');
});

it('não deixa gravar linha em outro laboratório', function (): void {
    $this->contexto->definir($this->alfa);

    DB::table('rls_teste')->insert(['laboratorio_id' => $this->beta, 'valor' => 'invasão']);
})->throws(QueryException::class);

it('não deixa mover uma linha para outro laboratório', function (): void {
    $this->contexto->definir($this->alfa);

    DB::table('rls_teste')->update(['laboratorio_id' => $this->beta]);
})->throws(QueryException::class);

it('a aplicação entra no banco sem poder ignorar o RLS', function (): void {
    $papel = DB::selectOne('select rolsuper, rolbypassrls from pg_roles where rolname = current_user');

    expect($papel->rolsuper)->toBeFalse()->and($papel->rolbypassrls)->toBeFalse();
});

it('toda tabela com laboratorio_id está sob RLS', function (): void {
    $semRls = DB::connection('pgsql_sistema')->select(<<<'SQL'
        select c.relname as tabela
        from pg_class c
        join pg_namespace n on n.oid = c.relnamespace and n.nspname = 'public'
        join pg_attribute a on a.attrelid = c.oid and a.attname = 'laboratorio_id' and not a.attisdropped
        where c.relkind = 'r' and not (c.relrowsecurity and c.relforcerowsecurity)
        SQL);

    $tabelas = array_diff(array_column($semRls, 'tabela'), TABELAS_SEM_RLS);

    expect($tabelas)->toBe([], 'Chame Rls::isolarPorLaboratorio() na migration destas tabelas.');
});

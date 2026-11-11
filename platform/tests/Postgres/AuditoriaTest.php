<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Vidra\Auditoria\Application\RegistrarNaTrilha;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

it('o Postgres esconde eventos de outro laboratório mesmo em SQL direto', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Precisa de Postgres com o papel vidra_app.');
    }
    expect(DB::selectOne('select current_user as nome')->nome)->toBe('vidra_app');
    $alfa = Laboratorios::criar();
    $beta = Laboratorios::criar('Beta');
    $contexto = app(ContextoLaboratorio::class);
    $contexto->executarComo($alfa, fn () => app(RegistrarNaTrilha::class)('pessoa_convidada', 'equipe', '1', 'Convidou Ana'));
    $contexto->executarComo($beta, fn () => expect(DB::table('registros_auditoria')->count())->toBe(0));
});

it('vidra_app não consegue atualizar nem apagar um evento por SQL', function (string $operacao): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Precisa de Postgres com o papel vidra_app.');
    }
    expect(DB::selectOne('select current_user as nome')->nome)->toBe('vidra_app');
    $lab = Laboratorios::criar();
    app(ContextoLaboratorio::class)->executarComo($lab, function () use ($operacao): void {
        app(RegistrarNaTrilha::class)('pessoa_convidada', 'equipe', '1', 'Convidou Ana');
        try {
            DB::transaction(fn () => $operacao === 'update'
                ? DB::table('registros_auditoria')->update(['detalhe' => 'Alterado'])
                : DB::table('registros_auditoria')->delete());
            test()->fail('A aplicação conseguiu alterar a trilha.');
        } catch (QueryException $e) {
            expect($e->errorInfo[0])->toBe('42501');
        }
    });
})->with(['update', 'delete']);

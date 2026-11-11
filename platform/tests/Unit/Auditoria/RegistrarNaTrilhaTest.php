<?php

declare(strict_types=1);

use Tests\Support\AuditoriaFixa;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\TrilhaEmMemoria;
use Vidra\Auditoria\Application\RegistrarNaTrilha;

it('registra ação autor laboratório ip e instante em UTC', function (): void {
    $repo = new TrilhaEmMemoria;
    $registrar = new RegistrarNaTrilha($repo, new AuditoriaFixa, new LaboratorioAtualFixo('alfa'));
    $registrar('pedido_aberto', 'pedido', 'pedido', 'Abriu o pedido P-00001', 'pedido');
    $r = $repo->registros[0];
    expect($repo->registros)->toHaveCount(1)
        ->and($r->usuarioId)->toBe(7)->and($r->usuarioNome)->toBe('Marina Alves')
        ->and($r->ip)->toBe('192.0.2.10')->and($r->laboratorioId)->toBe('alfa')
        ->and($r->em->format('Y-m-d H:i:s T'))->toBe('2026-11-10 13:32:00 UTC');
});

it('registra acesso público sem herdar o usuário autenticado', function (): void {
    $repo = new TrilhaEmMemoria;
    $registrar = new RegistrarNaTrilha($repo, new AuditoriaFixa, new LaboratorioAtualFixo('outro'));
    $registrar->aberturaPublica('alfa', 'laudo', 'pedido', '192.0.2.20');
    expect($repo->registros[0]->usuarioId)->toBeNull()
        ->and($repo->registros[0]->detalhe)->toBe('Tutor abriu pelo link')
        ->and($repo->registros[0]->laboratorioId)->toBe('alfa');
});

it('recusa alvo inválido e detalhe vazio', function (string $tipo, string $detalhe): void {
    (AuditoriaFixa::registrar())('pedido_aberto', $tipo, 'pedido', $detalhe);
})->with([['animal', 'Abriu'], ['pedido', ' ']])->throws(InvalidArgumentException::class);

<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;

function entrarComo(string $papel): Tests\TestCase
{
    $lab = Laboratorios::criar();

    return test()->actingAs(Laboratorios::usuarioEm($lab, $papel))->withSession(['laboratorio_id' => $lab]);
}

it('barra a área que o papel não usa', function (string $papel, string $rota): void {
    entrarComo($papel)->get(route($rota))->assertForbidden();
})->with([
    'bancada nos pedidos' => ['bancada', 'lab.pedidos.index'],
    'bancada na liberação' => ['bancada', 'lab.liberacao.index'],
    'bancada nos veterinários' => ['bancada', 'lab.veterinarios.index'],
    'recepção na bancada' => ['recepcao', 'lab.bancada.index'],
    'recepção na liberação' => ['recepcao', 'lab.liberacao.index'],
    'recepção nos exames' => ['recepcao', 'lab.exames.index'],
    'papel desconhecido' => ['visitante', 'lab.amostras.index'],
]);

it('abre a área que o papel usa', function (string $papel, string $rota): void {
    entrarComo($papel)->get(route($rota))->assertOk();
})->with([
    'bancada na bancada' => ['bancada', 'lab.bancada.index'],
    'bancada nas amostras' => ['bancada', 'lab.amostras.index'],
    'recepção nos pedidos' => ['recepcao', 'lab.pedidos.index'],
    'recepção nos tutores' => ['recepcao', 'lab.tutores.index'],
    'RT na liberação' => ['rt', 'lab.liberacao.index'],
    'RT nos exames' => ['rt', 'lab.exames.index'],
    'admin nos exames' => ['admin', 'lab.exames.index'],
]);

it('manda ao menu só as áreas do papel', function (): void {
    entrarComo('bancada')->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('laboratorio.areas', ['painel', 'laudos', 'amostras', 'bancada']));
});

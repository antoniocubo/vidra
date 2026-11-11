<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\Support\Laboratorios;
use Tests\Support\Veterinarios;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Veterinaria\Application\PedidosDoVeterinario\PedidosDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

it('a consulta do veterinário no contexto A não devolve pedidos de B no Postgres', function (): void {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Precisa do Postgres com schema migrado e usuário sujeito ao RLS.');
    }
    $sistema = DB::connection(config()->string('vidra.conexao_sistema'));
    // Duas conexões precisam enxergar a preparação; os UUIDs tornam a limpeza específica.
    $a = null;
    $b = null;
    $pessoa = null;
    try {
        $a = Laboratorios::criar('Alfa vet');
        $b = Laboratorios::criar('Beta vet');
        $vet = Veterinarios::comContaEm([$a, $b]);
        $pessoa = $vet->veterinario_id;
        $proprio = Veterinarios::pedido($a, Veterinarios::vinculoDa($vet, $a));
        Veterinarios::pedido($b, Veterinarios::vinculoDa($vet, $b));
        $contexto = app(ContextoLaboratorio::class);
        $lista = $contexto->executarComo($a, fn () => app(PedidosDoVeterinario::class)(new LaboratorioDoVeterinario($a, 'Alfa vet', Veterinarios::vinculoDa($vet, $a), false)));
        expect(array_column($lista->pedidos, 'id'))->toBe([$proprio]);
    } finally {
        app(ContextoLaboratorio::class)->limpar();
        $labs = array_values(array_filter([$a, $b]));
        $vets = $sistema->table('laboratorio_veterinario')->whereIn('laboratorio_id', $labs)->pluck('veterinario_id')->all();
        $usuarios = $sistema->table('laboratorio_usuario')->whereIn('laboratorio_id', $labs)->pluck('user_id')->all();
        $sistema->table('laboratorios')->whereIn('id', $labs)->delete();
        $sistema->table('veterinarios')->whereIn('id', array_unique([...$vets, ...($pessoa === null ? [] : [$pessoa])]))->delete();
        $sistema->table('users')->whereIn('id', $usuarios)->delete();
    }
});

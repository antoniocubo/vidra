<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Acesso;

use Illuminate\Support\Facades\DB;
use Vidra\Veterinaria\Domain\Acesso\AcessosDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

final class EloquentAcessosDoVeterinario implements AcessosDoVeterinario
{
    public function laboratoriosDo(string $veterinarioId): array
    {
        /** @var list<object{laboratorio_id: string, nome: string, vinculo_id: string, ve_pedidos_dos_colegas: bool}> $linhas */
        $linhas = DB::connection(config()->string('vidra.conexao_sistema'))
            ->table('laboratorio_veterinario')
            ->join('laboratorios', 'laboratorios.id', '=', 'laboratorio_veterinario.laboratorio_id')
            ->where('laboratorio_veterinario.veterinario_id', $veterinarioId)
            ->orderBy('laboratorios.nome')
            ->get(['laboratorios.id as laboratorio_id', 'laboratorios.nome', 'laboratorio_veterinario.id as vinculo_id', 'laboratorio_veterinario.ve_pedidos_dos_colegas'])
            ->all();

        return array_map(
            static fn (object $l): LaboratorioDoVeterinario => new LaboratorioDoVeterinario($l->laboratorio_id, $l->nome, $l->vinculo_id, (bool) $l->ve_pedidos_dos_colegas),
            $linhas,
        );
    }
}

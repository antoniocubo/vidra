<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Persistence;

use Illuminate\Support\Str;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;
use Vidra\Veterinaria\Domain\Vinculo\Vinculo;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoRepository;

final class EloquentVinculoRepository implements VinculoRepository
{
    public function nextIdentity(): VinculoId
    {
        return new VinculoId((string) Str::uuid7());
    }

    public function save(Vinculo $vinculo): void
    {
        VinculoModel::query()->updateOrCreate(['id' => $vinculo->id->value], [
            'laboratorio_id' => $vinculo->laboratorioId,
            'veterinario_id' => $vinculo->veterinarioId->value,
            'quem_paga' => $vinculo->quemPaga->value,
            'telefone_critico' => $vinculo->telefoneCritico->digits,
            've_pedidos_dos_colegas' => $vinculo->vePedidosDosColegas,
            'recebe_laudo' => $vinculo->recebeLaudo->value,
        ]);
    }

    public function existe(VeterinarioId $veterinarioId): bool
    {
        return VinculoModel::query()->where('veterinario_id', $veterinarioId->value)->exists();
    }
}

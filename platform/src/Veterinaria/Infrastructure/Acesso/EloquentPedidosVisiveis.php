<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Acesso;

use Vidra\Pedidos\Infrastructure\Persistence\PedidoModel;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\PedidosVisiveis;
use Vidra\Veterinaria\Infrastructure\Persistence\ClinicaVeterinarioModel;
use Vidra\Veterinaria\Infrastructure\Persistence\VinculoModel;

final readonly class EloquentPedidosVisiveis implements PedidosVisiveis
{
    public function __construct(private LaboratorioAtual $laboratorio) {}

    public function doLaboratorio(LaboratorioDoVeterinario $acesso): array
    {
        if ($acesso->laboratorioId !== $this->laboratorio->id() || ! VinculoModel::query()->whereKey($acesso->vinculoId)->exists()) {
            return [];
        }
        $ids = [$acesso->vinculoId];
        if ($acesso->vePedidosDosColegas) {
            $clinicas = ClinicaVeterinarioModel::query()->where('vinculo_id', $acesso->vinculoId)->pluck('clinica_id');
            $ids = [...$ids, ...ClinicaVeterinarioModel::query()->whereIn('clinica_id', $clinicas)->pluck('vinculo_id')->all()];
        }
        $pedidos = [];
        foreach (PedidoModel::query()->whereIn('solicitante_id', $ids)->orderByDesc('criado_em')->orderByDesc('id')->get() as $pedido) {
            $pedidos[$pedido->id] = $pedido->criado_em->toDateTimeImmutable();
        }

        return $pedidos;
    }
}

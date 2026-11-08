<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Laudos;

use Vidra\Laudos\Domain\PreferenciaDeEntrega;
use Vidra\Veterinaria\Infrastructure\Persistence\VinculoModel;

final readonly class PreferenciaDeEntregaVeterinaria implements PreferenciaDeEntrega
{
    public function doSolicitante(string $solicitanteId): string
    {
        return VinculoModel::query()->find($solicitanteId)->recebe_laudo ?? '';
    }
}

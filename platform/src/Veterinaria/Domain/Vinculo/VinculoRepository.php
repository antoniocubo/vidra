<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

interface VinculoRepository
{
    public function nextIdentity(): VinculoId;

    public function save(Vinculo $vinculo): void;

    /** Verifica o vínculo dentro do laboratório atual. */
    public function existe(VeterinarioId $veterinarioId): bool;
}

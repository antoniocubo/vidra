<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo;

use Vidra\Veterinaria\Domain\Clinica\ClinicaId;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

interface VinculoRepository
{
    public function nextIdentity(): VinculoId;

    /** Grava o vínculo e a lista de clínicas onde o veterinário atende. */
    public function save(Vinculo $vinculo): void;

    /** Verifica o vínculo dentro do laboratório atual. */
    public function existe(VeterinarioId $veterinarioId): bool;

    /** Só enxerga o laboratório atual: o vínculo de outro laboratório volta null. */
    public function porId(VinculoId $id): ?Vinculo;

    /** @return list<Vinculo> */
    public function todos(): array;

    /** @return list<Vinculo> */
    public function daClinica(ClinicaId $clinicaId): array;
}

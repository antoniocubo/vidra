<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\LaboratoriosDoVeterinario;

use Vidra\Veterinaria\Domain\Acesso\AcessosDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;

final readonly class LaboratoriosDoVeterinario
{
    public function __construct(private AcessosDoVeterinario $acessos) {}

    /** @return list<LaboratorioDoVeterinario> */
    public function __invoke(string $veterinarioId): array
    {
        return $this->acessos->laboratoriosDo($veterinarioId);
    }
}

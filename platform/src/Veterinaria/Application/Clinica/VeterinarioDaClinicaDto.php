<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Clinica;

final readonly class VeterinarioDaClinicaDto
{
    public function __construct(public string $vinculoId, public string $nome) {}
}

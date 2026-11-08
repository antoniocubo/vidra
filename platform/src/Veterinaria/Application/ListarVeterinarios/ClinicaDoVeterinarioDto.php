<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarVeterinarios;

final readonly class ClinicaDoVeterinarioDto
{
    public function __construct(public string $id, public string $nome, public bool $ativa) {}
}

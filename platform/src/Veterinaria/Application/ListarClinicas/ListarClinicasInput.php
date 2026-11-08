<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\ListarClinicas;

final readonly class ListarClinicasInput
{
    public function __construct(public string $busca = '', public bool $incluirInativas = false) {}
}

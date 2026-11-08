<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\BuscarTutores;

final readonly class BuscarTutoresInput
{
    public function __construct(public string $termo = '') {}
}

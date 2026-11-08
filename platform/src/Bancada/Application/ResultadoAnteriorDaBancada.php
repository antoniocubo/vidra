<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use DateTimeImmutable;

final readonly class ResultadoAnteriorDaBancada
{
    public function __construct(public ?string $valor, public ?string $texto, public DateTimeImmutable $liberadoEm) {}
}

<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

use DateTimeImmutable;

final readonly class ResultadoAnterior
{
    public function __construct(public ?Decimal $valor, public ?string $texto, public DateTimeImmutable $liberadoEm) {}
}

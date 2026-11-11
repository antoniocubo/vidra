<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

final readonly class LaudoLiberado
{
    public function __construct(public string $laudoId, public string $laboratorioId, public string $pedidoId, public DateTimeImmutable $liberadoEm, public int $versao = 1) {}
}

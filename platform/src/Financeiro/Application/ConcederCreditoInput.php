<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application;

final readonly class ConcederCreditoInput
{
    public function __construct(public string $faturaId, public string $motivo, public int $valorCentavos, public int $por) {}
}

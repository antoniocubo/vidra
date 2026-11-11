<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

final readonly class ReabrirLaudoInput
{
    public function __construct(public string $pedidoId, public string $motivo, public int $por) {}
}

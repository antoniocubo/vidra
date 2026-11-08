<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

final readonly class RegistrarAvisoInput
{
    public function __construct(public string $pedidoId, public string $resultadoId, public string $destinatario, public int $por) {}
}

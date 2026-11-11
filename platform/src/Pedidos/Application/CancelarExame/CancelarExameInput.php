<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\CancelarExame;

final readonly class CancelarExameInput
{
    public function __construct(public string $pedido, public string $item, public string $motivo, public int $por) {}
}

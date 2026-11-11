<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\IncluirExame;

final readonly class IncluirExameInput
{
    public function __construct(public string $pedido, public string $exameId, public int $por) {}
}

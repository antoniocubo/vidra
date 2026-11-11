<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\CancelarPedido;

final readonly class CancelarPedidoInput
{
    public function __construct(public string $pedido, public string $motivo, public int $por) {}
}

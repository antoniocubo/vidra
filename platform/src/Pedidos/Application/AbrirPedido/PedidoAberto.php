<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\AbrirPedido;

final readonly class PedidoAberto
{
    public function __construct(public string $pedidoId, public string $protocolo, public int $etiquetas) {}
}

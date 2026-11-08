<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ListarPedidos;

final readonly class ListaDePedidosDto
{
    /** @param list<PedidoResumoDto> $pedidos */
    public function __construct(public string $data, public array $pedidos) {}
}

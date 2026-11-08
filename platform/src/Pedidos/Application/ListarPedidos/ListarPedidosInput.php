<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ListarPedidos;

final readonly class ListarPedidosInput
{
    public function __construct(public ?string $data = null, public ?string $situacao = null, public string $busca = '') {}
}

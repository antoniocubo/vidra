<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class PedidoNaoEncontrado extends DomainException
{
    public static function para(): self
    {
        return new self('Pedido não encontrado.');
    }
}

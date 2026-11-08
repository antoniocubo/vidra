<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class PedidoInvalido extends DomainException
{
    public static function semExames(): self
    {
        return new self('Escolha pelo menos um exame.');
    }

    public static function exameRepetido(string $nome): self
    {
        return new self("O exame {$nome} já está no pedido.");
    }

    public static function itemInexistente(): self
    {
        return new self('Este exame não faz parte do pedido.');
    }

    public static function semExameAtivo(): self
    {
        return new self('O pedido precisa manter pelo menos um exame. Cancele o pedido inteiro, se for o caso.');
    }
}

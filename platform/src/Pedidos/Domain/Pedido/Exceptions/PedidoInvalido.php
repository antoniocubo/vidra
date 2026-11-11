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

    public static function semMotivo(): self
    {
        return new self('Diga o motivo do cancelamento.');
    }

    public static function motivoLongo(): self
    {
        return new self('Resuma o motivo em até 500 caracteres.');
    }

    public static function itemLiberado(string $nome): self
    {
        return new self("O exame {$nome} já foi liberado. Para corrigir, retifique o laudo.");
    }

    public static function itemJaCancelado(string $nome): self
    {
        return new self("O exame {$nome} já estava cancelado.");
    }

    public static function jaCancelado(): self
    {
        return new self('Este pedido já estava cancelado.');
    }

    public static function jaLiberado(): self
    {
        return new self('O laudo deste pedido já foi liberado. Para corrigir, retifique o laudo.');
    }

    public static function naoAceitaExame(): self
    {
        return new self('Só dá para incluir exame em pedido que ainda não foi liberado nem cancelado.');
    }
}

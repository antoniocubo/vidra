<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class PagadorInvalido extends DomainException
{
    public static function clinicaSemId(): self
    {
        return new self('Escolha a clínica que recebe a fatura.');
    }

    public static function clinicaSoParaClinica(): self
    {
        return new self('Só o pagador clínica tem clínica.');
    }
}

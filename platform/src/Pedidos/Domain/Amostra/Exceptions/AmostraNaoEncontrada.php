<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class AmostraNaoEncontrada extends DomainException
{
    public static function com(string $codigo): self
    {
        return new self("Nenhuma amostra com o código {$codigo} neste laboratório.");
    }

    public static function para(): self
    {
        return new self('Amostra não encontrada.');
    }
}

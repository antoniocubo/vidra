<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame\Exceptions;

final class AnalitoRepetido extends \Vidra\Shared\Domain\DomainException
{
    public static function para(string $nome): self
    {
        return new self("O analito {$nome} está repetido neste exame.");
    }
}

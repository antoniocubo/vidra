<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame\Exceptions;

final class CodigoDeExameJaUsado extends \Vidra\Shared\Domain\DomainException
{
    public static function para(string $codigo, string $nome): self
    {
        return new self("O código {$codigo} já é usado pelo exame {$nome}.");
    }
}

<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class CelularJaCadastrado extends DomainException
{
    public static function para(string $nome): self
    {
        return new self("Já existe um tutor com este celular: {$nome}.");
    }
}

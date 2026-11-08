<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class VinculoNaoEncontrado extends DomainException
{
    public static function para(): self
    {
        return new self('Veterinário não encontrado.');
    }
}

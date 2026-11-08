<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class TutorNaoEncontrado extends DomainException
{
    public static function para(): self
    {
        return new self('Tutor não encontrado.');
    }
}

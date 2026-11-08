<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class ClinicaNaoEncontrada extends DomainException
{
    public static function para(): self
    {
        return new self('Clínica não encontrada.');
    }
}

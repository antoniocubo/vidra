<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class LaboratorioSemAdministrador extends \Vidra\Shared\Domain\DomainException
{
    public static function para(): self
    {
        return new self('O laboratório precisa ter pelo menos um administrador.');
    }
}

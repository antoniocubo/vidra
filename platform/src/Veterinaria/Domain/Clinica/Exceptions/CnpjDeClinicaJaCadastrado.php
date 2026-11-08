<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica\Exceptions;

use Vidra\Shared\Domain\DomainException;

final class CnpjDeClinicaJaCadastrado extends DomainException
{
    public static function para(string $nomeFantasia): self
    {
        return new self("Já existe uma clínica com este CNPJ: {$nomeFantasia}.");
    }
}

<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Veterinario\Exceptions;

use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;
use Vidra\Veterinaria\Domain\Veterinario\VeterinarioId;

final class CrmvJaCadastrado extends DomainException
{
    private function __construct(string $message, public readonly VeterinarioId $existente)
    {
        parent::__construct($message);
    }

    public static function para(Crmv $crmv, VeterinarioId $existente): self
    {
        return new self("O {$crmv} já está cadastrado.", $existente);
    }
}

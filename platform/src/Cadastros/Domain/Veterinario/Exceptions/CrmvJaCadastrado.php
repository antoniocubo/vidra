<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Domain\Veterinario\Exceptions;

use Vidra\Cadastros\Domain\Veterinario\Crmv;
use Vidra\Cadastros\Domain\Veterinario\VeterinarioId;
use Vidra\Shared\Domain\DomainException;

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

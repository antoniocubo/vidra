<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Vinculo\Exceptions;

use Vidra\Shared\Domain\DomainException;
use Vidra\Veterinaria\Domain\Veterinario\Crmv;

final class VeterinarioJaVinculado extends DomainException
{
    public static function para(Crmv $crmv): self
    {
        return new self('Este veterinário já está cadastrado no seu laboratório.');
    }
}

<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

use Vidra\Shared\Domain\DomainException;

final class ErroDeBancada extends DomainException
{
    public static function com(string $mensagem): self
    {
        return new self($mensagem);
    }
}

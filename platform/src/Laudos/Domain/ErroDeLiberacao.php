<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use Vidra\Shared\Domain\DomainException;

final class ErroDeLiberacao extends DomainException
{
    public static function com(string $mensagem): self
    {
        return new self($mensagem);
    }
}

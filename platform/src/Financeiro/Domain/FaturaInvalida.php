<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use Vidra\Shared\Domain\DomainException;

final class FaturaInvalida extends DomainException
{
    public static function com(string $mensagem): self
    {
        return new self($mensagem);
    }
}

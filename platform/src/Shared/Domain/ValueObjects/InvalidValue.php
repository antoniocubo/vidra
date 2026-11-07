<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\ValueObjects;

use Vidra\Shared\Domain\DomainException;

final class InvalidValue extends DomainException
{
    public static function for(string $field, string $reason): self
    {
        return new self("{$field}: {$reason}");
    }
}

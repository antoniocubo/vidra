<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\ValueObjects;

use Stringable;

abstract readonly class Uuid implements Stringable
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    final public function __construct(public string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw InvalidValue::for(static::class, 'UUID inválido');
        }
    }

    public function equals(self $other): bool
    {
        return $other instanceof static && strtolower($this->value) === strtolower($other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

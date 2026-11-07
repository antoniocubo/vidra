<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\ValueObjects;

use Stringable;

final readonly class Email implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidValue::for('e-mail', "\"{$value}\" não é um e-mail válido");
        }

        $this->value = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

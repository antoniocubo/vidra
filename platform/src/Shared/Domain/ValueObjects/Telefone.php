<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain\ValueObjects;

use Stringable;

/**
 * Telefone brasileiro guardado só com dígitos (DDD + número).
 */
final readonly class Telefone implements Stringable
{
    public string $digits;

    public function __construct(string $value)
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (! in_array(strlen($digits), [10, 11], true)) {
            throw InvalidValue::for('telefone', 'use DDD + número, com 10 ou 11 dígitos');
        }

        $this->digits = $digits;
    }

    /** Formato de exibição: (19) 99641-2087 ou (19) 3241-0099. */
    public function formatted(): string
    {
        $ddd = substr($this->digits, 0, 2);
        $number = substr($this->digits, 2);
        $split = strlen($number) - 4;

        return sprintf('(%s) %s-%s', $ddd, substr($number, 0, $split), substr($number, $split));
    }

    public function __toString(): string
    {
        return $this->formatted();
    }
}

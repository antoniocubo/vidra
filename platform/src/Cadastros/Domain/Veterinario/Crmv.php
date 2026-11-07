<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Domain\Veterinario;

use Stringable;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;

/**
 * Registro no Conselho Regional de Medicina Veterinária. Único por UF.
 */
final readonly class Crmv implements Stringable
{
    public string $numero;

    public function __construct(string $numero, public Uf $uf)
    {
        $digits = preg_replace('/\D/', '', $numero) ?? '';

        if ($digits === '' || strlen($digits) > 6) {
            throw InvalidValue::for('CRMV', 'o número tem de 1 a 6 dígitos');
        }

        $this->numero = ltrim($digits, '0') ?: '0';
    }

    public function equals(self $other): bool
    {
        return $this->numero === $other->numero && $this->uf === $other->uf;
    }

    /** Como aparece no laudo: CRMV-SP 41208. */
    public function __toString(): string
    {
        return "CRMV-{$this->uf->value} {$this->numero}";
    }
}

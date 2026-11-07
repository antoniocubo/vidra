<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class Cnpj
{
    public string $value;

    public function __construct(string $value)
    {
        $digits = preg_replace('/[.\/\s-]/', '', $value) ?? '';
        if (! preg_match('/^\d{14}$/', $digits) || preg_match('/^(\d)\1{13}$/', $digits)) {
            throw InvalidValue::for('cnpj', 'informe um CNPJ válido');
        }
        for ($length = 12; $length <= 13; $length++) {
            $sum = 0;
            for ($i = 0; $i < $length; $i++) {
                $sum += (int) $digits[$i] * (($length - $i - 1) % 8 + 2);
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
            if ((int) $digits[$length] !== $digit) {
                throw InvalidValue::for('cnpj', 'informe um CNPJ válido');
            }
        }
        $this->value = $digits;
    }
}

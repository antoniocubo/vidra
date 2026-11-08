<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class Cpf
{
    public string $digits;

    public function __construct(string $valor)
    {
        $digits = preg_replace('/\D/', '', $valor) ?? '';
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            throw InvalidValue::for('CPF', 'informe um CPF válido');
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $digits[$i] * ($t + 1 - $i);
            }
            $digito = (10 * $soma) % 11;
            if (($digito === 10 ? 0 : $digito) !== (int) $digits[$t]) {
                throw InvalidValue::for('CPF', 'informe um CPF válido');
            }
        }
        $this->digits = $digits;
    }
}

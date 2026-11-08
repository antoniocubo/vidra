<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

final readonly class DeltaCheck
{
    // Provisório até o laboratório definir o limite por analito.
    public const int LIMITE_PERCENTUAL = 50;

    public function alerta(Decimal $novo, Decimal $anterior): bool
    {
        $base = abs($anterior->escalado());

        return $base === 0 ? $novo->escalado() !== 0 : abs($novo->escalado() - $anterior->escalado()) * 100 >= $base * self::LIMITE_PERCENTUAL;
    }

    public function percentual(Decimal $novo, Decimal $anterior): ?int
    {
        return $anterior->escalado() === 0 ? null : (int) round(abs($novo->escalado() - $anterior->escalado()) / abs($anterior->escalado()) * 100);
    }
}

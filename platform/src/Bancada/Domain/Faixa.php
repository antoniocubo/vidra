<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

final readonly class Faixa
{
    public function __construct(public string $perfil, public ?Decimal $minimo, public ?Decimal $maximo, public ?Decimal $criticoBaixo = null, public ?Decimal $criticoAlto = null) {}

    public function formatada(): string
    {
        $min = $this->minimo === null ? null : str_replace('.', ',', $this->minimo->value);
        $max = $this->maximo === null ? null : str_replace('.', ',', $this->maximo->value);

        return $min !== null && $max !== null ? "$min a $max" : ($min !== null ? "≥ $min" : "≤ $max");
    }
}

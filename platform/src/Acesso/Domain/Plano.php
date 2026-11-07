<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

enum Plano: string
{
    case Bancada = 'bancada';
    case Laboratorio = 'laboratorio';
    case Rede = 'rede';

    public function nome(): string
    {
        return match ($this) {
            self::Bancada => 'Bancada', self::Laboratorio => 'Laboratório', self::Rede => 'Rede'
        };
    }

    public function pedidos(): int
    {
        return match ($this) {
            self::Bancada => 600, self::Laboratorio => 2000, self::Rede => 6000
        };
    }

    public function usuarios(): ?int
    {
        return match ($this) {
            self::Bancada => 3, self::Laboratorio => 10, self::Rede => null
        };
    }

    public function unidades(): int
    {
        return $this === self::Rede ? 5 : 1;
    }

    public function precoEmCentavos(Cobranca $cobranca = Cobranca::Mensal): int
    {
        return (match ($this) {
            self::Bancada => 29000, self::Laboratorio => 59000, self::Rede => 119000
        }) * ($cobranca === Cobranca::Anual ? 10 : 1);
    }
}

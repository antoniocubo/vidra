<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

enum MotivoDeRejeicao: string
{
    case Hemolise = 'hemolise';
    case VolumeInsuficiente = 'volume_insuficiente';
    case TuboErrado = 'tubo_errado';
    case Coagulada = 'coagulada';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Hemolise => 'Hemólise',
            self::VolumeInsuficiente => 'Volume insuficiente',
            self::TuboErrado => 'Tubo errado para o exame',
            self::Coagulada => 'Amostra coagulada',
            self::Outro => 'Outro motivo',
        };
    }

    /** Como a clínica lê o problema: "chegou hemolisada". */
    public function frase(): string
    {
        return match ($this) {
            self::Hemolise => 'chegou hemolisada',
            self::VolumeInsuficiente => 'chegou com volume insuficiente',
            self::TuboErrado => 'chegou no tubo errado para o exame',
            self::Coagulada => 'chegou coagulada',
            self::Outro => 'chegou com problema',
        };
    }
}

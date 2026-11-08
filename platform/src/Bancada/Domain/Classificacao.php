<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

enum Classificacao: string
{
    case Normal = 'normal';
    case Baixo = 'baixo';
    case Alto = 'alto';
    case CriticoBaixo = 'critico_baixo';
    case CriticoAlto = 'critico_alto';
    case SemReferencia = 'sem_referencia';
    case Qualitativo = 'qualitativo';

    public function critica(): bool
    {
        return $this === self::CriticoBaixo || $this === self::CriticoAlto;
    }
}

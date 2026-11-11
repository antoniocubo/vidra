<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

enum SituacaoDaFatura: string
{
    case Aberta = 'aberta';
    case Enviada = 'enviada';
    case Paga = 'paga';
    case Vencida = 'vencida';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::Enviada => 'Enviada',
            self::Paga => 'Paga',
            self::Vencida => 'Vencida',
        };
    }
}

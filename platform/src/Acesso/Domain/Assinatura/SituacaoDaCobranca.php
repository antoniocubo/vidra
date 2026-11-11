<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

enum SituacaoDaCobranca: string
{
    case Pendente = 'pendente';
    case Paga = 'paga';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Paga => 'Paga',
            self::Vencida => 'Vencida',
            self::Cancelada => 'Cancelada',
        };
    }
}

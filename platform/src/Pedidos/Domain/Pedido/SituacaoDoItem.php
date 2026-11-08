<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

enum SituacaoDoItem: string
{
    case AguardandoAmostra = 'aguardando_amostra';
    case EmAnalise = 'em_analise';
    case ResultadoDigitado = 'resultado_digitado';
    case Liberado = 'liberado';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::AguardandoAmostra => 'Aguardando amostra',
            self::EmAnalise => 'Em análise',
            self::ResultadoDigitado => 'Aguardando liberação',
            self::Liberado => 'Liberado',
            self::Cancelado => 'Cancelado',
        };
    }
}

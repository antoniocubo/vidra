<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

enum SituacaoDoPedido: string
{
    case AguardandoAmostra = 'aguardando_amostra';
    case EmAnalise = 'em_analise';
    case AguardandoLiberacao = 'aguardando_liberacao';
    case Liberado = 'liberado';
    case Cancelado = 'cancelado';

    public function rotulo(): string
    {
        return match ($this) {
            self::AguardandoAmostra => 'Aguardando amostra',
            self::EmAnalise => 'Em análise',
            self::AguardandoLiberacao => 'Aguardando liberação',
            self::Liberado => 'Liberado',
            self::Cancelado => 'Cancelado',
        };
    }
}

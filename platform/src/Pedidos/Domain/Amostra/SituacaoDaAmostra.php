<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

enum SituacaoDaAmostra: string
{
    case Aguardando = 'aguardando';
    case Recebida = 'recebida';
    case Rejeitada = 'rejeitada';
    case AceitaComRessalva = 'aceita_com_ressalva';
}

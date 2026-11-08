<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

enum TipoDePagador: string
{
    case Clinica = 'clinica';
    case Solicitante = 'solicitante';
    case Responsavel = 'responsavel';
}

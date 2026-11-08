<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

enum Prioridade: string
{
    case Rotina = 'rotina';
    case Urgente = 'urgente';
}

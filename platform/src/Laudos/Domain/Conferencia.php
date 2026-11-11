<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

interface Conferencia
{
    /**
     * @return list<PedidoParaConferencia> */
    public function pedidos(): array;

    public function bloquear(string $pedidoId): void;

    public function reabrirItens(string $pedidoId): void;

    public function liberarItens(string $pedidoId): void;
}

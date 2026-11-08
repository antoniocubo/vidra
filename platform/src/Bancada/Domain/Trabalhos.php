<?php

declare(strict_types=1);

namespace Vidra\Bancada\Domain;

interface Trabalhos
{
    /**
     * @return list<Trabalho> */
    public function todos(): array;

    public function transicionar(string $pedidoId, string $itemId, string $situacao): void;

    /** Serializa concorrência com digitação, conferência e liberação do mesmo pedido. */
    public function bloquear(string $pedidoId): void;
}

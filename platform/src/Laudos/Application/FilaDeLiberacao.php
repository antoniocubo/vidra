<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\PedidoParaConferencia;

final readonly class FilaDeLiberacao
{
    public function __construct(private Conferencia $conferencia) {}

    /**
     * @return list<PedidoParaConferencia> */
    public function __invoke(): array
    {
        // Incompletos também entram: o RT vê o que falta em vez de o pedido sumir.
        return array_values(array_filter($this->conferencia->pedidos(), fn (PedidoParaConferencia $p): bool => $p->emConferencia()));
    }
}

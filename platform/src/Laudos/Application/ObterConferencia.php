<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Laudos\Domain\PedidoParaConferencia;

final readonly class ObterConferencia
{
    public function __construct(private Conferencia $conferencia) {}

    public function __invoke(string $id): PedidoParaConferencia
    {
        foreach ($this->conferencia->pedidos() as $p) {
            if ($p->id === $id) {
                return $p;
            }
        }
        throw new PedidoNaoEncontrado;
    }
}

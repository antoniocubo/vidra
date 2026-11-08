<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Vidra\Laudos\Domain\LaudoLiberado;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;

final readonly class DespacharEntregaDoLaudo
{
    public function handle(LaudoLiberado $evento): void
    {
        EntregarLaudo::dispatch($evento->laboratorioId, $evento->pedidoId);
    }
}

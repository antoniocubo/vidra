<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Vidra\Laudos\Domain\LaudoLiberado;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;

final readonly class DespacharEntregaDoLaudo
{
    public function __construct(private \Vidra\Laudos\Application\MontarLaudo\MontarLaudo $montar) {}

    public function handle(LaudoLiberado $evento): void
    {
        ($this->montar)($evento->pedidoId);
        EntregarLaudo::dispatch($evento->laboratorioId, $evento->pedidoId, 'completa', $evento->versao);
    }
}

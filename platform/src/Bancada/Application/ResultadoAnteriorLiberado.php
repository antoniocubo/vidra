<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\ResultadoRepository;

final readonly class ResultadoAnteriorLiberado
{
    public function __construct(private ResultadoRepository $resultados) {}

    public function __invoke(string $pacienteId, string $analitoId, string $pedidoId): ?ResultadoAnteriorDaBancada
    {
        $r = $this->resultados->anteriorLiberado($pacienteId, $analitoId, $pedidoId);

        return $r === null ? null : new ResultadoAnteriorDaBancada($r->valor?->value, $r->texto, $r->liberadoEm);
    }
}

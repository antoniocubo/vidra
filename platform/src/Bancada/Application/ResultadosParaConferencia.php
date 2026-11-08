<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\ResultadoRepository;

final readonly class ResultadosParaConferencia
{
    public function __construct(private ResultadoRepository $resultados) {}

    /** @return list<ResultadoDaBancada> */
    public function __invoke(string $pedidoId): array
    {
        return array_map(fn ($r): ResultadoDaBancada => new ResultadoDaBancada($r), $this->resultados->doPedido($pedidoId));
    }
}

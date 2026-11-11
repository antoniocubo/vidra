<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Acesso\Domain\Assinatura\PedidosDoMes;

final class PedidosDaAssinaturaFixos implements PedidosDoMes
{
    public function __construct(public int $total = 0) {}

    public function contar(string $laboratorioId, int $ano, int $mes): int
    {
        return $this->total;
    }
}

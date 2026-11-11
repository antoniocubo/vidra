<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

interface PedidosDoMes
{
    /** Pedidos abertos no mês (horário de São Paulo), sem os cancelados por inteiro. */
    public function contar(string $laboratorioId, int $ano, int $mes): int;
}

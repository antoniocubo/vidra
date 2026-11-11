<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/**
 * Porta para os pedidos: os liberados na competência, de quem paga por fatura,
 * ainda fora de qualquer fatura. A linha de produto diz quem é o pagador.
 */
interface PedidosFaturaveis
{
    /** @return list<FaturaPendente> um por pagador */
    public function daCompetencia(Competencia $competencia): array;
}

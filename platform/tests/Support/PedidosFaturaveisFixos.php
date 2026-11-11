<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\FaturaPendente;
use Vidra\Financeiro\Domain\PedidosFaturaveis;

/** Pedidos pendentes servidos a partir de uma lista fixa. */
final class PedidosFaturaveisFixos implements PedidosFaturaveis
{
    /** @param list<FaturaPendente> $pendentes */
    public function __construct(public array $pendentes = []) {}

    public function daCompetencia(Competencia $competencia): array
    {
        return $this->pendentes;
    }
}

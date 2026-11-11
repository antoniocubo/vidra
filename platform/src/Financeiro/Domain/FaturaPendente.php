<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

/** O que um pagador deve na competência e ainda não foi fechado em fatura. */
final readonly class FaturaPendente
{
    /** @param list<LinhaDaFatura> $linhas */
    public function __construct(public DadosDoPagador $dados, public array $linhas) {}

    public function total(): int
    {
        return array_sum(array_map(fn (LinhaDaFatura $l): int => $l->valorCentavos, $this->linhas));
    }
}

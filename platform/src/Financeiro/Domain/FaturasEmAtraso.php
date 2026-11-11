<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

interface FaturasEmAtraso
{
    /** @return list<Fatura> as não pagas de competências anteriores à informada */
    public function naoPagasAnterioresA(Competencia $competencia): array;
}

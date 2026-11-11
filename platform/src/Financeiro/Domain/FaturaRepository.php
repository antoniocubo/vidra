<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

interface FaturaRepository
{
    public function nextIdentity(): string;

    public function proximoNumero(): int;

    public function save(Fatura $fatura): void;

    public function porId(string $id): ?Fatura;

    public function doPagadorNaCompetencia(PagadorDaFatura $pagador, Competencia $competencia): ?Fatura;

    /** @return list<Fatura> */
    public function daCompetencia(Competencia $competencia): array;
}

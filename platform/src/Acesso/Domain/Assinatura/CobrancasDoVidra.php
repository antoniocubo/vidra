<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use DateTimeImmutable;

interface CobrancasDoVidra
{
    /** Grava se o pagamento ainda não existe; devolve o que está gravado. */
    public function registrar(CobrancaDoVidra $cobranca): CobrancaDoVidra;

    /** Atômico: só a primeira chamada devolve verdadeiro, o que torna o webhook idempotente. */
    public function marcarPaga(string $pagamentoId, DateTimeImmutable $quando): bool;

    public function marcarVencida(string $pagamentoId): void;

    /** @return list<CobrancaDoVidra> as mais novas primeiro */
    public function recentes(string $laboratorioId, int $limite): array;

    public function pendente(string $laboratorioId): ?CobrancaDoVidra;

    public function existeExcedente(string $laboratorioId, string $competencia): bool;
}

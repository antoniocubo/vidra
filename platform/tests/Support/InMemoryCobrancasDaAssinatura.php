<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Assinatura\TipoDeCobranca;

final class InMemoryCobrancasDaAssinatura implements CobrancasDoVidra
{
    /** @var array<string, CobrancaDoVidra> */
    public array $cobrancas = [];

    public function registrar(CobrancaDoVidra $cobranca): CobrancaDoVidra
    {
        return $this->cobrancas[$cobranca->pagamentoId] ??= $cobranca;
    }

    public function marcarPaga(string $pagamentoId, DateTimeImmutable $quando): bool
    {
        $c = $this->cobrancas[$pagamentoId];
        if ($c->situacao === SituacaoDaCobranca::Paga) {
            return false;
        }
        $this->cobrancas[$pagamentoId] = new CobrancaDoVidra($c->laboratorioId, $c->pagamentoId, $c->tipo, $c->competencia, $c->valorCentavos, SituacaoDaCobranca::Paga, $c->vencimento, $c->link, $quando);

        return true;
    }

    public function marcarVencida(string $pagamentoId): void
    {
        $c = $this->cobrancas[$pagamentoId];
        if ($c->situacao === SituacaoDaCobranca::Pendente) {
            $this->cobrancas[$pagamentoId] = new CobrancaDoVidra($c->laboratorioId, $c->pagamentoId, $c->tipo, $c->competencia, $c->valorCentavos, SituacaoDaCobranca::Vencida, $c->vencimento, $c->link);
        }
    }

    public function recentes(string $laboratorioId, int $limite): array
    {
        return array_slice(array_values(array_filter($this->cobrancas, fn (CobrancaDoVidra $c): bool => $c->laboratorioId === $laboratorioId)), 0, $limite);
    }

    public function pendente(string $laboratorioId): ?CobrancaDoVidra
    {
        foreach ($this->recentes($laboratorioId, 99) as $c) {
            if ($c->situacao !== SituacaoDaCobranca::Paga && $c->link !== null) {
                return $c;
            }
        }

        return null;
    }

    public function existeExcedente(string $laboratorioId, string $competencia): bool
    {
        foreach ($this->recentes($laboratorioId, 99) as $c) {
            if ($c->tipo === TipoDeCobranca::Excedente && $c->competencia === $competencia) {
                return true;
            }
        }

        return false;
    }
}

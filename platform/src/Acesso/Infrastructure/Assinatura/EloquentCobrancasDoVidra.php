<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Assinatura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use Vidra\Acesso\Domain\Assinatura\CobrancaDoVidra;
use Vidra\Acesso\Domain\Assinatura\CobrancasDoVidra;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Assinatura\TipoDeCobranca;

/** Tabela global (o webhook escreve sem laboratório atual): conexão de sistema e filtro explícito. */
final class EloquentCobrancasDoVidra implements CobrancasDoVidra
{
    public function registrar(CobrancaDoVidra $cobranca): CobrancaDoVidra
    {
        $this->tabela()->insertOrIgnore([
            'id' => (string) Str::uuid7(), 'laboratorio_id' => $cobranca->laboratorioId, 'asaas_pagamento_id' => $cobranca->pagamentoId,
            'tipo' => $cobranca->tipo->value, 'competencia' => $cobranca->competencia, 'valor_centavos' => $cobranca->valorCentavos,
            'situacao' => $cobranca->situacao->value, 'vencimento' => $cobranca->vencimento, 'link_pagamento' => $cobranca->link,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->mapear($this->tabela()->where('asaas_pagamento_id', $cobranca->pagamentoId)->firstOrFail());
    }

    public function marcarPaga(string $pagamentoId, DateTimeImmutable $quando): bool
    {
        return $this->tabela()->where('asaas_pagamento_id', $pagamentoId)
            ->whereIn('situacao', [SituacaoDaCobranca::Pendente->value, SituacaoDaCobranca::Vencida->value])
            ->update(['situacao' => SituacaoDaCobranca::Paga->value, 'paga_em' => $quando->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'updated_at' => now()]) === 1;
    }

    public function marcarVencida(string $pagamentoId): void
    {
        $this->tabela()->where('asaas_pagamento_id', $pagamentoId)->where('situacao', SituacaoDaCobranca::Pendente->value)
            ->update(['situacao' => SituacaoDaCobranca::Vencida->value, 'updated_at' => now()]);
    }

    public function recentes(string $laboratorioId, int $limite): array
    {
        return array_values($this->tabela()->where('laboratorio_id', $laboratorioId)->orderByDesc('vencimento')->orderByDesc('created_at')->limit($limite)
            ->get()->map(fn (stdClass $linha): CobrancaDoVidra => $this->mapear($linha))->all());
    }

    public function pendente(string $laboratorioId): ?CobrancaDoVidra
    {
        $linha = $this->tabela()->where('laboratorio_id', $laboratorioId)->whereIn('situacao', ['pendente', 'vencida'])->whereNotNull('link_pagamento')
            ->orderBy('vencimento')->first();

        return $linha === null ? null : $this->mapear($linha);
    }

    public function existeExcedente(string $laboratorioId, string $competencia): bool
    {
        return $this->tabela()->where('laboratorio_id', $laboratorioId)->where('tipo', TipoDeCobranca::Excedente->value)
            ->where('competencia', $competencia)->where('situacao', '<>', SituacaoDaCobranca::Cancelada->value)->exists();
    }

    private function conexao(): Connection
    {
        return DB::connection(config()->string('vidra.conexao_sistema'));
    }

    private function tabela(): \Illuminate\Database\Query\Builder
    {
        return $this->conexao()->table('cobrancas_vidra');
    }

    private function mapear(stdClass $linha): CobrancaDoVidra
    {
        return new CobrancaDoVidra(
            (string) $linha->laboratorio_id, (string) $linha->asaas_pagamento_id, TipoDeCobranca::from((string) $linha->tipo), $linha->competencia,
            (int) $linha->valor_centavos, SituacaoDaCobranca::from((string) $linha->situacao), substr((string) $linha->vencimento, 0, 10), $linha->link_pagamento,
            $linha->paga_em === null ? null : new DateTimeImmutable((string) $linha->paga_em, new DateTimeZone('UTC')),
        );
    }
}

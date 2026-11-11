<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Financeiro\Domain\Competencia;
use Vidra\Financeiro\Domain\Fatura;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\FaturasEmAtraso;
use Vidra\Financeiro\Domain\LinhaDaFatura;
use Vidra\Financeiro\Domain\NotaDeCredito;
use Vidra\Financeiro\Domain\PagadorDaFatura;
use Vidra\Financeiro\Domain\TipoDePagadorDaFatura;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class EloquentFaturaRepository implements FaturaRepository, FaturasEmAtraso
{
    private const string FUSO = 'America/Sao_Paulo';

    public function __construct(private LaboratorioAtual $atual) {}

    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function proximoNumero(): int
    {
        $chave = ['laboratorio_id' => $this->atual->id(), 'nome' => 'fatura'];
        DB::table('sequencias')->insertOrIgnore([...$chave, 'valor' => 0]);
        DB::table('sequencias')->where($chave)->lockForUpdate()->first();
        DB::table('sequencias')->where($chave)->increment('valor');

        return (int) DB::table('sequencias')->where($chave)->value('valor');
    }

    public function save(Fatura $f): void
    {
        $novo = ! FaturaModel::query()->whereKey($f->id)->exists();
        FaturaModel::query()->updateOrCreate(['id' => $f->id], [
            'laboratorio_id' => $f->laboratorioId, 'numero' => $f->numero, 'pagador_tipo' => $f->pagador->tipo->value, 'pagador_id' => $f->pagador->id,
            'pagador_nome' => $f->pagador->nome, 'pagador_email' => $f->pagador->email, 'competencia' => $f->competencia->valor(),
            'vencimento' => $f->vencimento->setTimezone(new DateTimeZone(self::FUSO))->format('Y-m-d'),
            'fechada_em' => $f->fechadaEm, 'enviada_em' => $f->enviadaEm(), 'paga_em' => $f->pagaEm(), 'paga_por' => $f->pagaPor(),
        ]);

        if ($novo) {
            foreach ($f->linhas as $l) {
                LinhaFaturaModel::query()->create([
                    'id' => (string) Str::uuid7(), 'laboratorio_id' => $f->laboratorioId, 'fatura_id' => $f->id, 'pedido_id' => $l->pedidoId, 'protocolo' => $l->protocolo,
                    'paciente' => $l->paciente, 'liberado_em' => $l->liberadoEm, 'exames' => $l->exames, 'valor_centavos' => $l->valorCentavos,
                ]);
            }
        }

        // Os créditos só crescem: regrava a lista inteira em vez de rastrear cada um.
        CreditoFaturaModel::query()->where('fatura_id', $f->id)->delete();
        foreach ($f->creditos() as $c) {
            CreditoFaturaModel::query()->create([
                'id' => (string) Str::uuid7(), 'laboratorio_id' => $f->laboratorioId, 'fatura_id' => $f->id, 'motivo' => $c->motivo, 'valor_centavos' => $c->valorCentavos, 'por' => $c->por, 'em' => $c->em,
            ]);
        }
    }

    public function porId(string $id): ?Fatura
    {
        if (! Str::isUuid($id)) {
            return null;
        }
        $m = FaturaModel::query()->with(['linhas', 'creditos'])->find($id);

        return $m === null ? null : $this->entidade($m);
    }

    public function doPagadorNaCompetencia(PagadorDaFatura $pagador, Competencia $competencia): ?Fatura
    {
        $m = FaturaModel::query()->with(['linhas', 'creditos'])
            ->where(['pagador_tipo' => $pagador->tipo->value, 'pagador_id' => $pagador->id, 'competencia' => $competencia->valor()])->first();

        return $m === null ? null : $this->entidade($m);
    }

    public function daCompetencia(Competencia $competencia): array
    {
        return array_values(FaturaModel::query()->with(['linhas', 'creditos'])->where('competencia', $competencia->valor())->orderBy('numero')->get()
            ->map(fn (FaturaModel $m): Fatura => $this->entidade($m))->all());
    }

    public function naoPagasAnterioresA(Competencia $competencia): array
    {
        return array_values(FaturaModel::query()->with(['linhas', 'creditos'])->where('competencia', '<', $competencia->valor())->whereNull('paga_em')->orderBy('numero')->get()
            ->map(fn (FaturaModel $m): Fatura => $this->entidade($m))->all());
    }

    private function entidade(FaturaModel $m): Fatura
    {
        $utc = new DateTimeZone('UTC');

        return Fatura::restaurar(
            $m->id, $m->laboratorio_id, $m->numero,
            new PagadorDaFatura(TipoDePagadorDaFatura::from($m->pagador_tipo), $m->pagador_id, $m->pagador_nome, $m->pagador_email),
            Competencia::de($m->competencia), new DateTimeImmutable(substr($m->vencimento, 0, 10).' 12:00:00', new DateTimeZone(self::FUSO)),
            array_values($m->linhas->map(fn (LinhaFaturaModel $l): LinhaDaFatura => new LinhaDaFatura($l->pedido_id, $l->protocolo, $l->paciente, $l->liberado_em->toDateTimeImmutable()->setTimezone($utc), $l->exames, $l->valor_centavos))->all()),
            $m->fechada_em->toDateTimeImmutable(), $m->enviada_em?->toDateTimeImmutable(), $m->paga_em?->toDateTimeImmutable(), $m->paga_por,
            array_values($m->creditos->map(fn (CreditoFaturaModel $c): NotaDeCredito => new NotaDeCredito($c->motivo, $c->valor_centavos, $c->por, $c->em->toDateTimeImmutable()))->all()),
        );
    }
}

<?php

declare(strict_types=1);

namespace Vidra\Bancada\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Vidra\Bancada\Domain\Classificacao;
use Vidra\Bancada\Domain\Decimal;
use Vidra\Bancada\Domain\Faixa;
use Vidra\Bancada\Domain\Resultado;
use Vidra\Bancada\Domain\ResultadoAnterior;
use Vidra\Bancada\Domain\ResultadoRepository;

final readonly class EloquentResultadoRepository implements ResultadoRepository
{
    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function save(Resultado $r): void
    {
        ResultadoModel::query()->updateOrCreate(['id' => $r->id], ['laboratorio_id' => $r->laboratorioId, 'pedido_id' => $r->pedidoId, 'item_id' => $r->itemId, 'paciente_id' => $r->pacienteId, 'analito_id' => $r->analitoId, 'nome' => $r->nome, 'unidade' => $r->unidade, 'valor' => $r->valor?->value, 'texto' => $r->texto, 'digitado_por' => $r->digitadoPor, 'digitador_nome' => $r->digitadorNome, 'digitado_em' => $r->digitadoEm, 'perfil' => $r->faixa?->perfil, 'minimo' => $r->faixa?->minimo?->value, 'maximo' => $r->faixa?->maximo?->value, 'critico_baixo' => $r->faixa?->criticoBaixo?->value, 'critico_alto' => $r->faixa?->criticoAlto?->value, 'classificacao' => $r->classificacao->value]);
    }

    public function doItem(string $itemId): array
    {
        return array_values(ResultadoModel::query()->where('item_id', $itemId)->get()->map(fn (ResultadoModel $m): Resultado => $this->montar($m))->all());
    }

    public function doPedido(string $pedidoId): array
    {
        return array_values(ResultadoModel::query()->where('pedido_id', $pedidoId)->get()->map(fn (ResultadoModel $m): Resultado => $this->montar($m))->all());
    }

    public function anteriorLiberado(string $pacienteId, string $analitoId, string $pedidoAtual): ?ResultadoAnterior
    {
        $m = ResultadoModel::query()->where('resultados.paciente_id', $pacienteId)->where('analito_id', $analitoId)->where('resultados.pedido_id', '!=', $pedidoAtual)
            ->join('laudos', function ($join): void {
                $join->on('laudos.pedido_id', '=', 'resultados.pedido_id')->on('laudos.laboratorio_id', '=', 'resultados.laboratorio_id');
            })
            ->join('pedido_itens', 'pedido_itens.id', '=', 'resultados.item_id')->where('pedido_itens.situacao', 'liberado')
            ->orderByDesc('laudos.liberado_em')->orderByDesc('resultados.digitado_em')->select('resultados.*', 'laudos.liberado_em as anterior_em')->first();

        return $m === null ? null : new ResultadoAnterior($m->valor === null ? null : new Decimal($m->valor), $m->texto, new DateTimeImmutable((string) $m->getAttribute('anterior_em')));
    }

    private function montar(ResultadoModel $m): Resultado
    {
        $d = static fn (?string $v): ?Decimal => $v === null ? null : new Decimal($v);

        return Resultado::restaurar($m->id, $m->laboratorio_id, $m->pedido_id, $m->item_id, $m->paciente_id, $m->analito_id, $m->nome, $m->unidade, $d($m->valor), $m->texto, $m->digitado_por, $m->digitador_nome, $m->digitado_em->toDateTimeImmutable(), $m->perfil === null ? null : new Faixa($m->perfil, $d($m->minimo), $d($m->maximo), $d($m->critico_baixo), $d($m->critico_alto)), Classificacao::from($m->classificacao));
    }
}

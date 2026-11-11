<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\CodigoDaAmostra;
use Vidra\Pedidos\Domain\Amostra\MotivoDeRejeicao;
use Vidra\Pedidos\Domain\Amostra\SituacaoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\PedidoId;

final readonly class EloquentAmostraRepository implements AmostraRepository
{
    public function nextIdentity(): AmostraId
    {
        return new AmostraId((string) Str::uuid7());
    }

    public function save(Amostra $amostra): void
    {
        AmostraModel::query()->updateOrCreate(['id' => $amostra->id->value], [
            'laboratorio_id' => $amostra->laboratorioId,
            'pedido_id' => $amostra->pedidoId->value,
            'codigo' => $amostra->codigo->value,
            'material' => $amostra->material,
            'situacao' => $amostra->situacao()->value,
            'recebida_em' => $amostra->recebidaEm(),
            'recebida_por' => $amostra->recebidaPor(),
            'motivo_rejeicao' => $amostra->motivo()?->value,
            'motivo_texto' => $amostra->motivoTexto(),
            'ressalva' => $amostra->ressalva(),
            'recoleta_codigo' => $amostra->recoleta()?->value,
        ]);

        $gravados = AmostraItemModel::query()->where('amostra_id', $amostra->id->value)->pluck('item_id')->all();
        AmostraItemModel::query()->where('amostra_id', $amostra->id->value)->whereNotIn('item_id', $amostra->itemIds)->delete();
        foreach (array_diff($amostra->itemIds, $gravados) as $itemId) {
            AmostraItemModel::query()->create(['laboratorio_id' => $amostra->laboratorioId, 'amostra_id' => $amostra->id->value, 'item_id' => $itemId]);
        }
    }

    public function porId(AmostraId $id): ?Amostra
    {
        $model = AmostraModel::query()->find($id->value);

        return $model === null ? null : $this->montar([$model])[0];
    }

    public function porCodigo(CodigoDaAmostra $codigo): ?Amostra
    {
        $model = AmostraModel::query()->where('codigo', $codigo->value)->first();

        return $model === null ? null : $this->montar([$model])[0];
    }

    public function doPedido(PedidoId $pedidoId): array
    {
        return $this->montar(array_values(AmostraModel::query()->where('pedido_id', $pedidoId->value)->orderBy('created_at')->orderBy('codigo')->get()->all()));
    }

    public function recebidasEntre(DateTimeImmutable $de, DateTimeImmutable $ate): array
    {
        return $this->montar(array_values(AmostraModel::query()->where('recebida_em', '>=', $de)->where('recebida_em', '<', $ate)->orderByDesc('recebida_em')->orderBy('codigo')->get()->all()));
    }

    public function aguardandoChegada(int $limite): array
    {
        return $this->montar(array_values(AmostraModel::query()->where('situacao', SituacaoDaAmostra::Aguardando->value)->whereIn('id', AmostraItemModel::query()->select('amostra_id'))->orderBy('created_at')->orderBy('codigo')->limit($limite)->get()->all()));
    }

    /**
     * @param  list<AmostraModel>  $models
     * @return list<Amostra>
     */
    private function montar(array $models): array
    {
        $itens = [];
        foreach (AmostraItemModel::query()->whereIn('amostra_id', array_map(fn (AmostraModel $m): string => $m->id, $models))->get() as $ligacao) {
            $itens[$ligacao->amostra_id][] = $ligacao->item_id;
        }

        return array_map(fn (AmostraModel $m): Amostra => Amostra::restaurar(
            new AmostraId($m->id), $m->laboratorio_id, new PedidoId($m->pedido_id), new CodigoDaAmostra($m->codigo), $m->material, $itens[$m->id] ?? [],
            SituacaoDaAmostra::from($m->situacao), $m->recebida_em?->toDateTimeImmutable(), $m->recebida_por,
            $m->motivo_rejeicao === null ? null : MotivoDeRejeicao::from($m->motivo_rejeicao), $m->motivo_texto, $m->ressalva,
            $m->recoleta_codigo === null ? null : new CodigoDaAmostra($m->recoleta_codigo),
        ), $models);
    }
}

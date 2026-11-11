<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Pedidos\Domain\Pedido\Cancelamento;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\ItemId;
use Vidra\Pedidos\Domain\Pedido\Pagador;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\Prioridade;
use Vidra\Pedidos\Domain\Pedido\Protocolo;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoItem;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class EloquentPedidoRepository implements PedidoRepository
{
    private const string SEQUENCIA_DO_PROTOCOLO = 'protocolo';

    public function __construct(private LaboratorioAtual $laboratorio) {}

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function nomesPorIds(array $ids): array
    {
        // Os ids vêm exclusivamente dos itens de pedidos já isolados pelo laboratório.
        $this->laboratorio->id();
        /** @var array<int, string> $nomes */
        $nomes = DB::table('users')->whereIn('id', $ids)->pluck('name', 'id')->all();

        return $nomes;
    }

    public function nextIdentity(): PedidoId
    {
        return new PedidoId((string) Str::uuid7());
    }

    public function nextItemIdentity(): ItemId
    {
        return new ItemId((string) Str::uuid7());
    }

    public function proximoProtocolo(): Protocolo
    {
        $laboratorioId = $this->laboratorio->id();
        $chave = ['laboratorio_id' => $laboratorioId, 'nome' => self::SEQUENCIA_DO_PROTOCOLO];

        // A primeira linha nasce com 0; as outras requisições esperam o bloqueio da linha até o fim da transação.
        DB::table('sequencias')->insertOrIgnore([...$chave, 'valor' => 0]);
        DB::table('sequencias')->where($chave)->lockForUpdate()->first();
        DB::table('sequencias')->where($chave)->increment('valor');

        return new Protocolo((int) DB::table('sequencias')->where($chave)->value('valor'));
    }

    public function save(Pedido $pedido): void
    {
        PedidoModel::query()->updateOrCreate(['id' => $pedido->id->value], [
            'laboratorio_id' => $pedido->laboratorioId,
            'protocolo' => $pedido->protocolo->numero,
            'paciente_id' => $pedido->pacienteId,
            'solicitante_id' => $pedido->solicitanteId,
            'pagador_tipo' => $pedido->pagador->tipo->value,
            'clinica_id' => $pedido->pagador->clinicaId,
            'prioridade' => $pedido->prioridade->value,
            'observacao' => $pedido->observacao,
            'criado_em' => $pedido->criadoEm,
            'criado_por' => $pedido->criadoPor,
        ]);

        foreach ($pedido->itens() as $ordem => $item) {
            ItemDoPedidoModel::query()->updateOrCreate(['id' => $item->id->value], [
                'laboratorio_id' => $pedido->laboratorioId,
                'pedido_id' => $pedido->id->value,
                'exame_id' => $item->exameId,
                'codigo' => $item->codigo,
                'nome' => $item->nome,
                'setor' => $item->setor,
                'material' => $item->material,
                'preco_tabela_centavos' => $item->precoTabelaCentavos,
                'desconto_percentual' => $item->descontoPercentual,
                'preco_centavos' => $item->precoCentavos,
                'situacao' => $item->situacao->value,
                'ordem' => $ordem,
                'cancelado_motivo' => $item->cancelamento?->motivo,
                'cancelado_por' => $item->cancelamento?->por,
                'cancelado_em' => $item->cancelamento?->em,
            ]);
        }
    }

    public function porId(PedidoId $id): ?Pedido
    {
        $model = PedidoModel::query()->find($id->value);

        return $model === null ? null : $this->montar([$model])[0];
    }

    public function porProtocolo(Protocolo $protocolo): ?Pedido
    {
        $model = PedidoModel::query()->where('protocolo', $protocolo->numero)->first();

        return $model === null ? null : $this->montar([$model])[0];
    }

    public function porIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $pedidos = [];
        foreach ($this->montar(array_values(PedidoModel::query()->whereIn('id', $ids)->get()->all())) as $pedido) {
            $pedidos[$pedido->id->value] = $pedido;
        }

        return $pedidos;
    }

    public function criadosEntre(DateTimeImmutable $de, DateTimeImmutable $ate, int $limite): array
    {
        $models = PedidoModel::query()->where('criado_em', '>=', $de)->where('criado_em', '<', $ate)
            ->orderByDesc('criado_em')->orderByDesc('protocolo')->limit($limite)->get();

        return $this->montar(array_values($models->all()));
    }

    /**
     * Junta os itens de todos os pedidos numa consulta só.
     *
     * @param  list<PedidoModel>  $models
     * @return list<Pedido>
     */
    private function montar(array $models): array
    {
        $itens = [];
        $ids = array_map(fn (PedidoModel $m): string => $m->id, $models);
        foreach (ItemDoPedidoModel::query()->whereIn('pedido_id', $ids)->orderBy('ordem')->get() as $item) {
            $itens[$item->pedido_id][] = ItemDoPedido::restaurar(
                new ItemId($item->id), $item->exame_id, $item->codigo, $item->nome, $item->setor, $item->material,
                $item->preco_tabela_centavos, $item->desconto_percentual, $item->preco_centavos, SituacaoDoItem::from($item->situacao),
                $item->cancelado_motivo === null || $item->cancelado_por === null || $item->cancelado_em === null ? null : new Cancelamento($item->cancelado_motivo, $item->cancelado_por, $item->cancelado_em->toDateTimeImmutable()),
            );
        }

        return array_map(fn (PedidoModel $m): Pedido => Pedido::restaurar(
            new PedidoId($m->id), $m->laboratorio_id, new Protocolo($m->protocolo), $m->paciente_id, $m->solicitante_id,
            new Pagador(TipoDePagador::from($m->pagador_tipo), $m->clinica_id), Prioridade::from($m->prioridade),
            $m->criado_em->toDateTimeImmutable(), $m->criado_por, $m->observacao, $itens[$m->id] ?? [],
        ), $models);
    }
}

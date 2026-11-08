<?php

declare(strict_types=1);

namespace Vidra\Painel\Infrastructure;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Vidra\Painel\Domain\LeituraDoPainel;

final readonly class ConsultaDoPainel implements LeituraDoPainel
{
    public function indicadores(DateTimeImmutable $inicio, DateTimeImmutable $fim): array
    {
        $linha = DB::query()
            ->selectSub($this->hoje($inicio, $fim)->selectRaw('count(*)'), 'pedidosHoje')
            ->selectSub(RegistroDoPainel::tabela('amostras')->where('situacao', 'aguardando')->selectRaw('count(*)'), 'amostrasAguardando')
            ->selectSub(RegistroDoPainel::tabela('pedido_itens')->where('situacao', 'em_analise')->selectRaw('count(*)'), 'naBancada')
            ->selectSub($this->fila()->selectRaw('count(*)'), 'aguardandoLiberacao')
            ->selectSub(RegistroDoPainel::tabela('laudos')->where('liberado_em', '>=', $inicio)->where('liberado_em', '<', $fim)->selectRaw('count(*)'), 'laudosHoje')
            ->first();

        return array_map(intval(...), (array) $linha);
    }

    public function atencao(DateTimeImmutable $agora): array
    {
        $pedidos = RegistroDoPainel::tabela('pedidos')->select('id', 'protocolo', 'criado_em');
        $itens = RegistroDoPainel::tabela('pedido_itens')->select('id', 'pedido_id', 'situacao', 'exame_id', 'nome');
        $criticos = RegistroDoPainel::tabela('resultados')
            ->joinSub(clone $pedidos, 'p', 'p.id', '=', 'resultados.pedido_id')
            ->joinSub(clone $itens, 'i', 'i.id', '=', 'resultados.item_id')
            ->whereNotIn('i.situacao', ['cancelado', 'liberado'])
            ->whereIn('classificacao', ['critico_alto', 'critico_baixo'])
            ->whereNotExists(RegistroDoPainel::tabela('avisos_valor_critico')->selectRaw('1')
                ->whereColumn('resultado_id', 'resultados.id')
                ->whereColumn('valor_avisado', 'resultados.valor')
                ->whereColumn('resultado_digitado_em', 'resultados.digitado_em'))
            ->selectRaw("resultados.id, p.id as pedido_id, p.protocolo, 'critico' as tipo, resultados.nome as detalhe, p.id as destino, p.criado_em, 0 as prazo, 1 as ordem");

        $recoletas = RegistroDoPainel::tabela('amostras')
            ->joinSub(clone $pedidos, 'p', 'p.id', '=', 'amostras.pedido_id')
            ->where('situacao', 'rejeitada')->whereNotNull('recoleta_codigo')
            ->whereExists(RegistroDoPainel::tabela('amostras as nova')->selectRaw('1')
                ->whereColumn('nova.pedido_id', 'amostras.pedido_id')->whereColumn('nova.codigo', 'amostras.recoleta_codigo')
                ->whereNull('nova.recebida_em')->where('nova.situacao', 'aguardando'))
            ->selectRaw("amostras.id, p.id as pedido_id, p.protocolo, 'recoleta' as tipo, amostras.material as detalhe, amostras.id as destino, p.criado_em, 0 as prazo, 2 as ordem");

        $urgentes = RegistroDoPainel::tabela('pedidos')->where('prioridade', 'urgente')
            ->whereExists($this->itensAtivos()->whereNotIn('situacao', ['liberado']))
            ->selectRaw("pedidos.id, pedidos.id as pedido_id, protocolo, 'urgente' as tipo, 'Pedido urgente ainda não liberado' as detalhe, pedidos.id as destino, criado_em, 0 as prazo, 3 as ordem");

        $atrasados = RegistroDoPainel::tabela('pedido_itens')
            ->joinSub(clone $pedidos, 'p', 'p.id', '=', 'pedido_itens.pedido_id')
            ->joinSub(RegistroDoPainel::tabela('exames')->select('id', 'prazo_horas'), 'e', 'e.id', '=', 'pedido_itens.exame_id')
            ->whereNotIn('situacao', ['liberado', 'cancelado']);
        // A aplicação calcula o texto; aqui o banco filtra antes do limite de oito linhas.
        if (DB::connection()->getDriverName() === 'pgsql') {
            $atrasados->whereRaw('EXTRACT(EPOCH FROM (CAST(? AS timestamp) - p.criado_em)) > e.prazo_horas * 3600', [$agora->format('Y-m-d H:i:s')]);
        } else {
            $atrasados->whereRaw("(CAST(strftime('%s', ?) AS INTEGER) - CAST(strftime('%s', p.criado_em) AS INTEGER)) > e.prazo_horas * 3600", [$agora->format('Y-m-d H:i:s')]);
        }
        $atrasados->selectRaw("pedido_itens.id, p.id as pedido_id, p.protocolo, 'atrasado' as tipo, pedido_itens.nome as detalhe, p.id as destino, p.criado_em, e.prazo_horas as prazo, 4 as ordem");

        return array_map(static fn (object $r): array => [
            'id' => $r->tipo.':'.$r->id, 'pedidoId' => (string) $r->pedido_id, 'protocolo' => (int) $r->protocolo,
            'tipo' => (string) $r->tipo, 'detalhe' => (string) $r->detalhe, 'destino' => (string) $r->destino,
            'criadoEm' => (string) $r->criado_em, 'prazo' => (int) $r->prazo,
        ], array_values(DB::query()->fromSub($criticos->unionAll($recoletas)->unionAll($urgentes)->unionAll($atrasados), 'atencao')
            ->orderBy('ordem')->orderBy('criado_em')->orderBy('id')->limit(8)->get()->all()));
    }

    public function andamento(DateTimeImmutable $inicio, DateTimeImmutable $fim): array
    {
        $estados = RegistroDoPainel::tabela('pedido_itens')->where('situacao', '<>', 'cancelado')
            ->selectRaw("pedido_id, count(*) as total, sum(case when situacao = 'liberado' then 1 else 0 end) as liberados, sum(case when situacao = 'aguardando_amostra' then 1 else 0 end) as aguardando, sum(case when situacao = 'resultado_digitado' then 1 else 0 end) as digitados")
            ->groupBy('pedido_id');

        return array_map(static fn (object $r): array => [
            'id' => (string) $r->id, 'protocolo' => (int) $r->protocolo,
            'situacao' => match (true) {
                (int) $r->total === 0 => 'cancelado',
                (int) $r->total === (int) $r->liberados => 'liberado',
                (int) $r->total === (int) $r->aguardando => 'aguardando_amostra',
                (int) $r->total === (int) $r->digitados + (int) $r->liberados => 'aguardando_liberacao',
                default => 'em_analise',
            },
        ], array_values($this->hoje($inicio, $fim)->leftJoinSub($estados, 'e', 'e.pedido_id', '=', 'pedidos.id')
            ->select('pedidos.id', 'protocolo', 'e.total', 'e.liberados', 'e.aguardando', 'e.digitados')
            ->orderByDesc('criado_em')->orderByDesc('protocolo')->limit(20)->get()->all()));
    }

    private function hoje(DateTimeImmutable $inicio, DateTimeImmutable $fim): Builder
    {
        return RegistroDoPainel::tabela('pedidos')->where('criado_em', '>=', $inicio)->where('criado_em', '<', $fim);
    }

    private function itensAtivos(): Builder
    {
        return RegistroDoPainel::tabela('pedido_itens')->selectRaw('1')->whereColumn('pedido_id', 'pedidos.id')->where('situacao', '<>', 'cancelado');
    }

    private function fila(): Builder
    {
        return RegistroDoPainel::tabela('pedidos')->whereExists($this->itensAtivos())
            ->whereNotExists($this->itensAtivos()->where('situacao', '<>', 'resultado_digitado'));
    }
}

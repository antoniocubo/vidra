<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Vidra\Bancada\Application\ResultadosParaConferencia;
use Vidra\Bancada\Infrastructure\TrabalhosDosPedidos;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\PedidoParaConferencia;
use Vidra\Laudos\Domain\ResultadoParaConferencia;
use Vidra\Pedidos\Application\ParaBancada\ConsultarTrabalho;
use Vidra\Pedidos\Application\ParaBancada\TransicionarItem;

final readonly class ConferenciaDosPedidos implements Conferencia
{
    public function __construct(private ConsultarTrabalho $consultar, private TransicionarItem $transicionar, private ResultadosParaConferencia $resultados, private LaudoRepository $laudos, private TrabalhosDosPedidos $trabalhos, private \Vidra\Bancada\Application\AvaliarMudanca $mudanca, private \Vidra\Bancada\Application\ResultadoAnteriorLiberado $anterior, private \Vidra\Laudos\Application\MontarLaudo\MontarLaudo $montar) {}

    public function pedidos(): array
    {
        $grupos = [];
        foreach (($this->consultar)() as $i) {
            if ($i->situacao !== 'cancelado') {
                $grupos[$i->pedidoId][] = $i;
            }
        }
        $pedidos = [];
        foreach ($grupos as $id => $itens) {
            $primeiro = $itens[0];
            $estados = [];
            $exames = [];
            foreach ($itens as $i) {
                $estados[$i->id] = $i->situacao;
                $exames[$i->id] = $i->exame;
            }
            $avisos = [];
            foreach ($this->laudos->avisos($id) as $a) {
                $avisos[$a->resultadoId] = $a;
            }
            $linhas = [];
            foreach (($this->resultados)($id) as $r) {
                if (! isset($estados[$r->itemId])) {
                    continue;
                }
                $a = ($this->anterior)($r->pacienteId, $r->analitoId, $id);
                $mudanca = ($this->mudanca)($r->valor, $a?->valor);
                $valor = $r->valor ?? $r->texto ?? '';
                $anteriorValor = $a === null ? null : ($a->valor ?? $a->texto);
                $linhas[] = new ResultadoParaConferencia($r->id, $r->itemId, $exames[$r->itemId], $r->nome, $valor, $r->unidade, $r->classificacao, $r->referencia, $r->digitadorNome, $r->digitadoEm, $anteriorValor, $a?->liberadoEm, $mudanca['alerta'], $mudanca['percentual'], $avisos[$r->id] ?? null);
            }
            $pedidos[] = new PedidoParaConferencia($id, $primeiro->laboratorioId, $primeiro->protocolo, $primeiro->paciente, $primeiro->solicitante, $primeiro->telefoneCritico, $estados, $linhas);
        }
        usort($pedidos, fn ($a, $b): int => strcmp($a->protocolo, $b->protocolo));

        return $pedidos;
    }

    public function bloquear(string $pedidoId): void
    {
        $this->trabalhos->bloquear($pedidoId);
    }

    public function reabrirItens(string $pedidoId): void
    {
        ($this->montar)($pedidoId);
        foreach (($this->consultar)([$pedidoId]) as $i) {
            if ($i->situacao === 'liberado') {
                $this->transicionar->reabrirParaRetificacao($pedidoId, $i->id);
            }
        }
    }

    public function liberarItens(string $pedidoId): void
    {
        foreach (($this->consultar)([$pedidoId]) as $i) {
            if ($i->situacao !== 'cancelado') {
                ($this->transicionar)($pedidoId, $i->id, 'liberado');
            }
        }
    }
}

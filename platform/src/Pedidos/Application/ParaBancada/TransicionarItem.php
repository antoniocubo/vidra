<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\ParaBancada;

use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\Pedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\SituacaoDoItem;

final readonly class TransicionarItem
{
    public function __construct(private PedidoRepository $pedidos) {}

    public function reabrirParaRetificacao(string $pedidoId, string $itemId): void
    {
        $this->transicionar($pedidoId, $itemId, 'resultado_digitado', SituacaoDoItem::Liberado);
    }

    public function __invoke(string $pedidoId, string $itemId, string $destino): void
    {
        $origem = match ($destino) {
            'resultado_digitado' => SituacaoDoItem::EmAnalise,
            'em_analise', 'liberado' => SituacaoDoItem::ResultadoDigitado,
            default => throw PedidoInvalido::itemInexistente(),
        };
        $this->transicionar($pedidoId, $itemId, $destino, $origem);
    }

    private function transicionar(string $pedidoId, string $itemId, string $destino, SituacaoDoItem $origem): void
    {
        $p = $this->pedidos->porId(new PedidoId($pedidoId)) ?? throw PedidoInvalido::itemInexistente();
        $itens = [];
        $encontrado = false;
        foreach ($p->itens() as $i) {
            if ($i->id->value !== $itemId) {
                $itens[] = $i;

                continue;
            }
            $encontrado = true;
            if ($i->situacao !== $origem) {
                throw PedidoInvalido::itemInexistente();
            }
            $itens[] = ItemDoPedido::restaurar($i->id, $i->exameId, $i->codigo, $i->nome, $i->setor, $i->material, $i->precoTabelaCentavos, $i->descontoPercentual, $i->precoCentavos, SituacaoDoItem::from($destino));
        }
        if (! $encontrado) {
            throw PedidoInvalido::itemInexistente();
        }
        $this->pedidos->save(Pedido::restaurar($p->id, $p->laboratorioId, $p->protocolo, $p->pacienteId, $p->solicitanteId, $p->pagador, $p->prioridade, $p->criadoEm, $p->criadoPor, $p->observacao, $itens));
    }
}

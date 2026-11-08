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

    public function __invoke(string $pedidoId, string $itemId, string $destino): void
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
            $permitido = match ($destino) {
                'resultado_digitado' => $i->situacao === SituacaoDoItem::EmAnalise, 'em_analise' => $i->situacao === SituacaoDoItem::ResultadoDigitado, 'liberado' => $i->situacao === SituacaoDoItem::ResultadoDigitado, default => false
            };
            if (! $permitido) {
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

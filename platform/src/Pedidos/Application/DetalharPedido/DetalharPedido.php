<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\DetalharPedido;

use Vidra\Pedidos\Application\CancelarExame\NomesDosCanceladores;
use Vidra\Pedidos\Application\Formato;
use Vidra\Pedidos\Domain\Amostra\Amostra;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\SituacaoDaAmostra;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoNaoEncontrado;
use Vidra\Pedidos\Domain\Pedido\ItemDoPedido;
use Vidra\Pedidos\Domain\Pedido\PedidoId;
use Vidra\Pedidos\Domain\Pedido\PedidoRepository;
use Vidra\Pedidos\Domain\Pedido\TipoDePagador;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Pedidos\Domain\Portas\Solicitantes;
use Vidra\Pedidos\Domain\Portas\Vocabulario;

final readonly class DetalharPedido
{
    public function __construct(
        private PedidoRepository $pedidos,
        private AmostraRepository $amostras,
        private Pacientes $pacientes,
        private Solicitantes $solicitantes,
        private Pagadores $pagadores,
        private Vocabulario $vocabulario,
        private NomesDosCanceladores $canceladores,
    ) {}

    public function __invoke(string $pedidoId): PedidoDto
    {
        $pedido = $this->pedidos->porId(new PedidoId($pedidoId)) ?? throw PedidoNaoEncontrado::para();
        $paciente = $this->pacientes->resumo($pedido->pacienteId);
        $solicitante = $this->solicitantes->resumo($pedido->solicitanteId);
        $textos = $this->vocabulario->textos();
        $clinica = $pedido->pagador->clinicaId === null ? null : $this->pagadores->clinica($pedido->pagador->clinicaId);
        $amostras = array_values(array_filter($this->amostras->doPedido($pedido->id), fn (Amostra $a): bool => $a->itemIds !== []));
        $nomes = $this->canceladores->nomesPorIds(array_values(array_unique(array_filter(array_map(fn (ItemDoPedido $i): ?int => $i->cancelamento?->por, $pedido->itens())))));
        $podeEditar = $pedido->situacao() !== \Vidra\Pedidos\Domain\Pedido\SituacaoDoPedido::Cancelado
            && array_filter($pedido->itens(), fn (ItemDoPedido $i): bool => $i->situacao === \Vidra\Pedidos\Domain\Pedido\SituacaoDoItem::Liberado) === [];
        $estado = $pedido->situacao();

        $rotuloDoPagador = match ($pedido->pagador->tipo) {
            TipoDePagador::Clinica => ($clinica->nome ?? 'Clínica removida').' · fatura mensal',
            TipoDePagador::Solicitante => $textos->pagadorSolicitante.' · fatura mensal',
            TipoDePagador::Responsavel => $textos->pagadorResponsavel.' · balcão',
        };

        return new PedidoDto(
            $pedido->id->value,
            (string) $pedido->protocolo,
            [
                'nome' => $paciente->nome ?? 'Paciente removido',
                'descricao' => $paciente->descricao ?? '',
                'especieCurta' => $paciente->especieCurta ?? '',
                'responsavelNome' => $paciente->responsavelNome ?? '',
                'rotuloDoResponsavel' => $textos->responsavel,
            ],
            ['nome' => $solicitante->nome ?? 'Solicitante removido', 'registro' => $solicitante->registro ?? ''],
            ['tipo' => $pedido->pagador->tipo->value, 'rotulo' => $rotuloDoPagador, 'descontoPercentual' => $clinica->descontoPercentual ?? 0],
            $pedido->prioridade->value,
            $estado->value,
            $estado->rotulo(),
            Formato::dataEHora($pedido->criadoEm),
            $pedido->observacao,
            array_map(fn (ItemDoPedido $i): array => [
                'exameId' => $i->exameId, 'id' => $i->id->value, 'codigo' => $i->codigo, 'nome' => $i->nome, 'material' => $i->material,
                'cancelamento' => $i->cancelamento === null ? null : ['motivo' => $i->cancelamento->motivo, 'por' => $nomes[$i->cancelamento->por] ?? 'Usuário removido', 'em' => Formato::dataEHora($i->cancelamento->em)],
                'situacao' => $i->situacao->value, 'situacaoRotulo' => $i->situacao->rotulo(), 'precoCentavos' => $i->precoCentavos,
            ], $pedido->itens()),
            array_map(fn (Amostra $a): array => [
                'id' => $a->id->value, 'codigo' => $a->codigo->value, 'material' => $a->material,
                'situacao' => $a->situacao()->value, 'recebidaEm' => $a->recebidaEm() === null ? null : Formato::hora($a->recebidaEm()),
            ], $amostras),
            $pedido->total(),
            count(array_filter($amostras, fn (Amostra $a): bool => $a->situacao() !== SituacaoDaAmostra::Rejeitada)),
            $podeEditar,
            $podeEditar,
        );
    }
}

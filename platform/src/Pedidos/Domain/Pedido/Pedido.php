<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;

/**
 * O pedido de exames de um paciente. A situação não é gravada: sai dos itens.
 */
final class Pedido
{
    /** @param list<ItemDoPedido> $itens */
    private function __construct(
        public readonly PedidoId $id,
        public readonly string $laboratorioId,
        public readonly Protocolo $protocolo,
        public readonly string $pacienteId,
        public readonly string $solicitanteId,
        public readonly Pagador $pagador,
        public readonly Prioridade $prioridade,
        public readonly DateTimeImmutable $criadoEm,
        public readonly int $criadoPor,
        public readonly ?string $observacao,
        private array $itens,
    ) {}

    /** @param list<ItemDoPedido> $itens */
    public static function abrir(
        PedidoId $id, string $laboratorioId, Protocolo $protocolo, string $pacienteId, string $solicitanteId,
        Pagador $pagador, Prioridade $prioridade, DateTimeImmutable $criadoEm, int $criadoPor, ?string $observacao, array $itens,
    ): self {
        if ($itens === []) {
            throw PedidoInvalido::semExames();
        }

        $vistos = [];
        foreach ($itens as $item) {
            if (isset($vistos[$item->exameId])) {
                throw PedidoInvalido::exameRepetido($item->nome);
            }
            $vistos[$item->exameId] = true;
        }

        $observacao = $observacao === null ? null : trim($observacao);

        return new self(
            $id, $laboratorioId, $protocolo, $pacienteId, $solicitanteId, $pagador, $prioridade,
            $criadoEm->setTimezone(new DateTimeZone('UTC')), $criadoPor, $observacao === '' ? null : $observacao, $itens,
        );
    }

    /**
     * Reconstrói a partir da persistência, sem rodar regras de criação.
     *
     * @param  list<ItemDoPedido>  $itens
     */
    public static function restaurar(
        PedidoId $id, string $laboratorioId, Protocolo $protocolo, string $pacienteId, string $solicitanteId,
        Pagador $pagador, Prioridade $prioridade, DateTimeImmutable $criadoEm, int $criadoPor, ?string $observacao, array $itens,
    ): self {
        return new self($id, $laboratorioId, $protocolo, $pacienteId, $solicitanteId, $pagador, $prioridade, $criadoEm, $criadoPor, $observacao, $itens);
    }

    /** @return list<ItemDoPedido> */
    public function itens(): array
    {
        return $this->itens;
    }

    public function item(ItemId $id): ItemDoPedido
    {
        foreach ($this->itens as $item) {
            if ($item->id->equals($id)) {
                return $item;
            }
        }

        throw PedidoInvalido::itemInexistente();
    }

    /** Cancela um exame; o pedido precisa continuar com pelo menos um. */
    public function cancelarItem(ItemId $id): void
    {
        $this->item($id);
        $restantes = array_filter($this->itens, fn (ItemDoPedido $item): bool => ! $item->id->equals($id) && ! $item->cancelado());
        if ($restantes === []) {
            throw PedidoInvalido::semExameAtivo();
        }

        $this->trocarItens($id, fn (ItemDoPedido $item): ItemDoPedido => $item->cancelar());
    }

    /** @param list<string> $itemIds os itens de uma amostra que acabou de chegar */
    public function iniciarAnaliseDe(array $itemIds): void
    {
        $this->trocarVarios($itemIds, fn (ItemDoPedido $item): ItemDoPedido => $item->iniciarAnalise());
    }

    /** @param list<string> $itemIds os itens de uma amostra rejeitada: voltam a esperar uma nova coleta */
    public function aguardarNovaAmostraDe(array $itemIds): void
    {
        $this->trocarVarios($itemIds, fn (ItemDoPedido $item): ItemDoPedido => $item->voltarAAguardarAmostra());
    }

    /** Soma o que será cobrado, sem os exames cancelados. */
    public function total(): int
    {
        return array_sum(array_map(fn (ItemDoPedido $item): int => $item->cancelado() ? 0 : $item->precoCentavos, $this->itens));
    }

    /**
     * Sai dos itens: sem nenhum resultado ainda é "aguardando amostra" se nada chegou,
     * "em análise" quando há trabalho em andamento, "aguardando liberação" quando
     * todos têm resultado, e "liberado" quando todos foram assinados.
     */
    public function situacao(): SituacaoDoPedido
    {
        $ativos = array_values(array_filter($this->itens, fn (ItemDoPedido $item): bool => ! $item->cancelado()));
        if ($ativos === []) {
            return SituacaoDoPedido::Cancelado;
        }

        $situacoes = array_map(fn (ItemDoPedido $item): SituacaoDoItem => $item->situacao, $ativos);
        $todos = fn (SituacaoDoItem ...$aceitas): bool => array_filter($situacoes, fn (SituacaoDoItem $situacao): bool => ! in_array($situacao, $aceitas, true)) === [];

        return match (true) {
            $todos(SituacaoDoItem::Liberado) => SituacaoDoPedido::Liberado,
            $todos(SituacaoDoItem::AguardandoAmostra) => SituacaoDoPedido::AguardandoAmostra,
            $todos(SituacaoDoItem::ResultadoDigitado, SituacaoDoItem::Liberado) => SituacaoDoPedido::AguardandoLiberacao,
            default => SituacaoDoPedido::EmAnalise,
        };
    }

    public function urgente(): bool
    {
        return $this->prioridade === Prioridade::Urgente;
    }

    /** @param callable(ItemDoPedido): ItemDoPedido $mudanca */
    private function trocarItens(ItemId $id, callable $mudanca): void
    {
        $this->itens = array_map(fn (ItemDoPedido $item): ItemDoPedido => $item->id->equals($id) ? $mudanca($item) : $item, $this->itens);
    }

    /**
     * @param  list<string>  $itemIds
     * @param  callable(ItemDoPedido): ItemDoPedido  $mudanca
     */
    private function trocarVarios(array $itemIds, callable $mudanca): void
    {
        $this->itens = array_map(fn (ItemDoPedido $item): ItemDoPedido => in_array($item->id->value, $itemIds, true) ? $mudanca($item) : $item, $this->itens);
    }
}

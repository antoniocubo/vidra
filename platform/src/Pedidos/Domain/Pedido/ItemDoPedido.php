<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Pedido;

use Vidra\Pedidos\Domain\Pedido\Exceptions\PedidoInvalido;
use Vidra\Pedidos\Domain\Portas\ExameDoPedido;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

/**
 * Um exame pedido. Código, nome, setor, material e preço são cópias do momento do
 * pedido: mudar o catálogo depois não muda o que o laboratório combinou nem cobrou.
 */
final readonly class ItemDoPedido
{
    private function __construct(
        public ItemId $id,
        public string $exameId,
        public string $codigo,
        public string $nome,
        public string $setor,
        public string $material,
        public int $precoTabelaCentavos,
        public int $descontoPercentual,
        public int $precoCentavos,
        public SituacaoDoItem $situacao,
        public ?Cancelamento $cancelamento = null,
    ) {}

    /** Cota o exame pela tabela do laboratório, com o desconto da clínica quando houver. */
    public static function cotar(ItemId $id, ExameDoPedido $exame, int $descontoPercentual = 0): self
    {
        if ($descontoPercentual < 0 || $descontoPercentual > 100) {
            throw InvalidValue::for('desconto', 'use um valor de 0 a 100');
        }

        return new self(
            $id, $exame->id, $exame->codigo, $exame->nome, $exame->setor, $exame->material,
            $exame->precoCentavos, $descontoPercentual, self::comDesconto($exame->precoCentavos, $descontoPercentual),
            SituacaoDoItem::AguardandoAmostra,
        );
    }

    public static function restaurar(
        ItemId $id, string $exameId, string $codigo, string $nome, string $setor, string $material,
        int $precoTabelaCentavos, int $descontoPercentual, int $precoCentavos, SituacaoDoItem $situacao, ?Cancelamento $cancelamento = null,
    ): self {
        return new self($id, $exameId, $codigo, $nome, $setor, $material, $precoTabelaCentavos, $descontoPercentual, $precoCentavos, $situacao, $cancelamento);
    }

    /** Arredonda para o centavo, metade para cima. */
    public static function comDesconto(int $precoCentavos, int $descontoPercentual): int
    {
        return intdiv($precoCentavos * (100 - $descontoPercentual) + 50, 100);
    }

    public function iniciarAnalise(): self
    {
        return $this->situacao === SituacaoDoItem::AguardandoAmostra ? $this->com(SituacaoDoItem::EmAnalise) : $this;
    }

    public function voltarAAguardarAmostra(): self
    {
        return $this->situacao === SituacaoDoItem::EmAnalise ? $this->com(SituacaoDoItem::AguardandoAmostra) : $this;
    }

    /** Vale até a liberação; depois disso o caminho é retificar o laudo. */
    public function cancelar(Cancelamento $cancelamento): self
    {
        if ($this->situacao === SituacaoDoItem::Liberado) {
            throw PedidoInvalido::itemLiberado($this->nome);
        }
        if ($this->cancelado()) {
            throw PedidoInvalido::itemJaCancelado($this->nome);
        }

        return new self($this->id, $this->exameId, $this->codigo, $this->nome, $this->setor, $this->material, $this->precoTabelaCentavos, $this->descontoPercentual, $this->precoCentavos, SituacaoDoItem::Cancelado, $cancelamento);
    }

    public function cancelado(): bool
    {
        return $this->situacao === SituacaoDoItem::Cancelado;
    }

    private function com(SituacaoDoItem $situacao): self
    {
        return new self($this->id, $this->exameId, $this->codigo, $this->nome, $this->setor, $this->material, $this->precoTabelaCentavos, $this->descontoPercentual, $this->precoCentavos, $situacao, $this->cancelamento);
    }
}

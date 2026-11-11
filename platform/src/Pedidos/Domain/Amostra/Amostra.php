<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Pedidos\Domain\Amostra\Exceptions\TriagemInvalida;
use Vidra\Pedidos\Domain\Pedido\PedidoId;

/** Um tubo (ou recipiente) do pedido, do momento da etiqueta até a triagem. */
final class Amostra
{
    /** @param list<string> $itemIds ids dos itens do pedido que usam esta amostra */
    private function __construct(
        public readonly AmostraId $id,
        public readonly string $laboratorioId,
        public readonly PedidoId $pedidoId,
        public readonly CodigoDaAmostra $codigo,
        public readonly string $material,
        public private(set) array $itemIds,
        private SituacaoDaAmostra $situacao,
        private ?DateTimeImmutable $recebidaEm,
        private ?int $recebidaPor,
        private ?MotivoDeRejeicao $motivo,
        private ?string $motivoTexto,
        private ?string $ressalva,
        private ?CodigoDaAmostra $recoleta,
    ) {}

    /** @param list<string> $itemIds */
    public static function gerar(AmostraId $id, string $laboratorioId, PedidoId $pedidoId, CodigoDaAmostra $codigo, string $material, array $itemIds): self
    {
        return new self($id, $laboratorioId, $pedidoId, $codigo, $material, $itemIds, SituacaoDaAmostra::Aguardando, null, null, null, null, null, null);
    }

    /**
     * Reconstrói a partir da persistência, sem rodar regras de criação.
     *
     * @param  list<string>  $itemIds
     */
    public static function restaurar(
        AmostraId $id, string $laboratorioId, PedidoId $pedidoId, CodigoDaAmostra $codigo, string $material, array $itemIds,
        SituacaoDaAmostra $situacao, ?DateTimeImmutable $recebidaEm, ?int $recebidaPor, ?MotivoDeRejeicao $motivo,
        ?string $motivoTexto, ?string $ressalva, ?CodigoDaAmostra $recoleta,
    ): self {
        return new self($id, $laboratorioId, $pedidoId, $codigo, $material, $itemIds, $situacao, $recebidaEm, $recebidaPor, $motivo, $motivoTexto, $ressalva, $recoleta);
    }

    /** O tubo chegou ao laboratório. Receber duas vezes é erro: avisa a que horas foi a primeira. */
    public function receber(DateTimeImmutable $quando, int $por): void
    {
        if ($this->situacao !== SituacaoDaAmostra::Aguardando) {
            throw TriagemInvalida::jaRecebida($this->codigo->value, $this->recebidaEm ?? $quando);
        }

        $this->situacao = SituacaoDaAmostra::Recebida;
        $this->recebidaEm = $quando->setTimezone(new DateTimeZone('UTC'));
        $this->recebidaPor = $por;
    }

    /** A triagem recusa a amostra. Com motivo "outro", o texto é obrigatório. */
    public function rejeitar(MotivoDeRejeicao $motivo, ?string $texto = null): void
    {
        if ($this->situacao !== SituacaoDaAmostra::Recebida && $this->situacao !== SituacaoDaAmostra::Aguardando) {
            throw TriagemInvalida::jaTriada($this->codigo->value);
        }

        $texto = self::textoOuNulo($texto);
        if ($motivo === MotivoDeRejeicao::Outro && $texto === null) {
            throw TriagemInvalida::motivoSemTexto();
        }

        $this->situacao = SituacaoDaAmostra::Rejeitada;
        $this->motivo = $motivo;
        $this->motivoTexto = $texto;
    }

    /** A amostra serve, mas o laudo precisa dizer o que há de errado com ela. */
    public function aceitarComRessalva(string $ressalva): void
    {
        if ($this->situacao === SituacaoDaAmostra::Aguardando) {
            throw TriagemInvalida::aindaNaoRecebida($this->codigo->value);
        }

        if ($this->situacao !== SituacaoDaAmostra::Recebida) {
            throw TriagemInvalida::jaTriada($this->codigo->value);
        }

        $ressalva = self::textoOuNulo($ressalva) ?? throw TriagemInvalida::ressalvaSemTexto();
        $this->situacao = SituacaoDaAmostra::AceitaComRessalva;
        $this->ressalva = $ressalva;
    }

    /** Só de uma amostra rejeitada: devolve a nova, com o próximo código, para os mesmos exames. */
    public function pedirRecoleta(AmostraId $novoId, CodigoDaAmostra $novoCodigo): self
    {
        if ($this->situacao !== SituacaoDaAmostra::Rejeitada) {
            throw TriagemInvalida::soRejeitadaPedeRecoleta($this->codigo->value);
        }

        if ($this->recoleta !== null) {
            throw TriagemInvalida::recoletaJaPedida($this->codigo->value, $this->recoleta->value);
        }

        $this->recoleta = $novoCodigo;

        return self::gerar($novoId, $this->laboratorioId, $this->pedidoId, $novoCodigo, $this->material, $this->itemIds);
    }

    public function incluirItem(string $itemId): void
    {
        $this->exigirPendente();
        if (! in_array($itemId, $this->itemIds, true)) {
            $this->itemIds[] = $itemId;
        }
    }

    public function retirarItem(string $itemId): void
    {
        $this->exigirPendente();
        $this->itemIds = array_values(array_filter($this->itemIds, fn (string $id): bool => $id !== $itemId));
    }

    private function exigirPendente(): void
    {
        if ($this->situacao !== SituacaoDaAmostra::Aguardando) {
            throw TriagemInvalida::jaTriada($this->codigo->value);
        }
    }

    public function situacao(): SituacaoDaAmostra
    {
        return $this->situacao;
    }

    public function recebidaEm(): ?DateTimeImmutable
    {
        return $this->recebidaEm;
    }

    public function recebidaPor(): ?int
    {
        return $this->recebidaPor;
    }

    public function motivo(): ?MotivoDeRejeicao
    {
        return $this->motivo;
    }

    public function motivoTexto(): ?string
    {
        return $this->motivoTexto;
    }

    /** Texto que vai para o laudo. */
    public function ressalva(): ?string
    {
        return $this->ressalva;
    }

    /** Código da amostra que substituiu esta, quando a recoleta já foi pedida. */
    public function recoleta(): ?CodigoDaAmostra
    {
        return $this->recoleta;
    }

    private static function textoOuNulo(?string $texto): ?string
    {
        $texto = $texto === null ? null : trim($texto);

        return $texto === '' ? null : $texto;
    }
}

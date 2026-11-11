<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use DateTimeImmutable;
use DateTimeZone;

/**
 * A fatura mensal de uma clínica ou de um veterinário. Fechada, só recebe envio;
 * enviada, fica imutável: o único ajuste é a nota de crédito. Vencida não é
 * gravada: sai da data de vencimento.
 */
final class Fatura
{
    /**
     * @param  list<LinhaDaFatura>  $linhas
     * @param  list<NotaDeCredito>  $creditos
     */
    private function __construct(
        public readonly string $id,
        public readonly string $laboratorioId,
        public readonly int $numero,
        public readonly PagadorDaFatura $pagador,
        public readonly Competencia $competencia,
        public readonly DateTimeImmutable $vencimento,
        public readonly array $linhas,
        public readonly DateTimeImmutable $fechadaEm,
        private ?DateTimeImmutable $enviadaEm,
        private ?DateTimeImmutable $pagaEm,
        private ?int $pagaPor,
        private array $creditos,
    ) {}

    /** @param list<LinhaDaFatura> $linhas */
    public static function fechar(
        string $id, string $laboratorioId, int $numero, PagadorDaFatura $pagador, Competencia $competencia,
        DateTimeImmutable $vencimento, array $linhas, DateTimeImmutable $fechadaEm,
    ): self {
        if ($linhas === []) {
            throw FaturaInvalida::com('Não há pedidos liberados para faturar nesta competência.');
        }
        $pedidos = array_map(fn (LinhaDaFatura $l): string => $l->pedidoId, $linhas);
        if (count(array_unique($pedidos)) !== count($pedidos)) {
            throw FaturaInvalida::com('Um pedido apareceu duas vezes na mesma fatura.');
        }
        usort($linhas, fn (LinhaDaFatura $a, LinhaDaFatura $b): int => $a->liberadoEm <=> $b->liberadoEm ?: strcmp($a->protocolo, $b->protocolo));

        return new self($id, $laboratorioId, $numero, $pagador, $competencia, $vencimento, $linhas, self::utc($fechadaEm), null, null, null, []);
    }

    /**
     * @param  list<LinhaDaFatura>  $linhas
     * @param  list<NotaDeCredito>  $creditos
     */
    public static function restaurar(
        string $id, string $laboratorioId, int $numero, PagadorDaFatura $pagador, Competencia $competencia, DateTimeImmutable $vencimento,
        array $linhas, DateTimeImmutable $fechadaEm, ?DateTimeImmutable $enviadaEm, ?DateTimeImmutable $pagaEm, ?int $pagaPor, array $creditos,
    ): self {
        return new self($id, $laboratorioId, $numero, $pagador, $competencia, $vencimento, $linhas, $fechadaEm, $enviadaEm, $pagaEm, $pagaPor, $creditos);
    }

    public function enviar(DateTimeImmutable $em): void
    {
        if ($this->enviadaEm !== null) {
            throw FaturaInvalida::com('Esta fatura já foi enviada. Use "Reenviar" para mandar de novo.');
        }
        if ($this->pagador->email === null) {
            throw FaturaInvalida::com("Cadastre o e-mail de faturamento de {$this->pagador->nome} antes de enviar.");
        }

        $this->enviadaEm = self::utc($em);
    }

    /** Baixa manual: o laboratório confirma que recebeu. */
    public function registrarPagamento(DateTimeImmutable $em, int $por): void
    {
        if ($this->enviadaEm === null) {
            throw FaturaInvalida::com('Envie a fatura antes de registrar o pagamento.');
        }
        if ($this->pagaEm !== null) {
            throw FaturaInvalida::com('Esta fatura já está paga.');
        }

        $this->pagaEm = self::utc($em);
        $this->pagaPor = $por;
    }

    public function creditar(NotaDeCredito $credito): void
    {
        if ($this->enviadaEm === null) {
            throw FaturaInvalida::com('Antes de enviar, feche a fatura de novo em vez de dar crédito.');
        }
        if ($this->pagaEm !== null) {
            throw FaturaInvalida::com('A fatura já está paga; não cabe crédito.');
        }
        if ($credito->valorCentavos > $this->total()) {
            throw FaturaInvalida::com('O crédito não pode passar do total da fatura.');
        }

        $this->creditos[] = $credito;
    }

    public function subtotal(): int
    {
        return array_sum(array_map(fn (LinhaDaFatura $l): int => $l->valorCentavos, $this->linhas));
    }

    public function totalCreditos(): int
    {
        return array_sum(array_map(fn (NotaDeCredito $c): int => $c->valorCentavos, $this->creditos));
    }

    public function total(): int
    {
        return $this->subtotal() - $this->totalCreditos();
    }

    public function situacao(DateTimeImmutable $agora): SituacaoDaFatura
    {
        $local = new DateTimeZone('America/Sao_Paulo');

        return match (true) {
            $this->pagaEm !== null => SituacaoDaFatura::Paga,
            $this->enviadaEm === null => SituacaoDaFatura::Aberta,
            $agora->setTimezone($local)->format('Y-m-d') > $this->vencimento->setTimezone($local)->format('Y-m-d') => SituacaoDaFatura::Vencida,
            default => SituacaoDaFatura::Enviada,
        };
    }

    public function numeroFormatado(): string
    {
        return 'F-'.str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function enviadaEm(): ?DateTimeImmutable
    {
        return $this->enviadaEm;
    }

    public function pagaEm(): ?DateTimeImmutable
    {
        return $this->pagaEm;
    }

    public function pagaPor(): ?int
    {
        return $this->pagaPor;
    }

    /** @return list<NotaDeCredito> */
    public function creditos(): array
    {
        return $this->creditos;
    }

    private static function utc(DateTimeImmutable $quando): DateTimeImmutable
    {
        return $quando->setTimezone(new DateTimeZone('UTC'));
    }
}

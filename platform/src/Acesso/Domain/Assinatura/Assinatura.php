<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain\Assinatura;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

/**
 * A assinatura do Vidra por um laboratório. 14 dias de teste; depois vale até
 * `pagoAte`. Atrasada ganha uma tolerância antes de virar "só consulta":
 * o laboratório nunca perde acesso aos laudos que já emitiu.
 */
final class Assinatura
{
    public const int DIAS_DE_TOLERANCIA = 5;

    public const int DIAS_DE_AVISO = 5;

    private function __construct(
        public readonly string $laboratorioId,
        private Plano $plano,
        private Cobranca $cobranca,
        public readonly DateTimeImmutable $testeTerminaEm,
        private ?DateTimeImmutable $pagoAte,
    ) {}

    public static function restaurar(string $laboratorioId, Plano $plano, Cobranca $cobranca, DateTimeImmutable $testeTerminaEm, ?DateTimeImmutable $pagoAte): self
    {
        return new self($laboratorioId, $plano, $cobranca, $testeTerminaEm, $pagoAte);
    }

    public function situacao(DateTimeImmutable $agora): SituacaoDaAssinatura
    {
        $validaAte = $this->validaAte();

        return match (true) {
            $this->pagoAte === null && $agora < $this->testeTerminaEm => SituacaoDaAssinatura::EmTeste,
            $agora < $validaAte => SituacaoDaAssinatura::Ativa,
            $this->pagoAte !== null && $agora < $validaAte->modify('+'.self::DIAS_DE_TOLERANCIA.' days') => SituacaoDaAssinatura::Atrasada,
            default => SituacaoDaAssinatura::SomenteConsulta,
        };
    }

    /** Fim do teste ou do período pago, o que vier depois. */
    public function validaAte(): DateTimeImmutable
    {
        return $this->pagoAte !== null && $this->pagoAte > $this->testeTerminaEm ? $this->pagoAte : $this->testeTerminaEm;
    }

    /** Dias inteiros até perder o acesso pleno; 0 no último dia. */
    public function diasRestantes(DateTimeImmutable $agora): int
    {
        $fuso = new DateTimeZone('America/Sao_Paulo');
        $hoje = new DateTimeImmutable($agora->setTimezone($fuso)->format('Y-m-d'), $fuso);
        $fim = new DateTimeImmutable($this->validaAte()->setTimezone($fuso)->format('Y-m-d'), $fuso);

        return max(0, (int) $hoje->diff($fim)->format('%r%a'));
    }

    /** Hora de lembrar o laboratório: perto do fim do teste ou do período pago, ou já atrasado. */
    public function deveAvisar(DateTimeImmutable $agora): bool
    {
        return match ($this->situacao($agora)) {
            SituacaoDaAssinatura::Atrasada, SituacaoDaAssinatura::SomenteConsulta => true,
            default => $this->diasRestantes($agora) <= self::DIAS_DE_AVISO,
        };
    }

    /** O gateway confirmou o pagamento: estende a partir do fim atual, sem perder dias. */
    public function registrarPagamento(DateTimeImmutable $agora): void
    {
        $base = $this->validaAte() > $agora ? $this->validaAte() : $agora;
        $this->pagoAte = $base->modify($this->cobranca === Cobranca::Anual ? '+1 year' : '+1 month');
    }

    public function trocarPlano(Plano $plano, Cobranca $cobranca): void
    {
        $this->plano = $plano;
        $this->cobranca = $cobranca;
    }

    public function consumo(int $pedidosNoMes): ConsumoDoMes
    {
        return new ConsumoDoMes($this->plano, $this->cobranca, $pedidosNoMes);
    }

    public function plano(): Plano
    {
        return $this->plano;
    }

    public function cobranca(): Cobranca
    {
        return $this->cobranca;
    }

    public function pagoAte(): ?DateTimeImmutable
    {
        return $this->pagoAte;
    }
}

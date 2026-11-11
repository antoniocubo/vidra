<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use DateTimeImmutable;
use DateTimeZone;

/** O mês faturado, no horário do laboratório (America/Sao_Paulo). */
final readonly class Competencia
{
    private const string FUSO = 'America/Sao_Paulo';

    public function __construct(public int $ano, public int $mes)
    {
        if ($mes < 1 || $mes > 12 || $ano < 2000 || $ano > 2999) {
            throw FaturaInvalida::com('Competência inválida.');
        }
    }

    public static function de(string $anoMes): self
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $anoMes, $m) !== 1) {
            throw FaturaInvalida::com('Competência inválida. Use o formato 2026-11.');
        }

        return new self((int) $m[1], (int) $m[2]);
    }

    /** O mês anterior ao dia informado: no fechamento de 01/12 fatura-se novembro. */
    public static function anteriorA(DateTimeImmutable $dia): self
    {
        $local = $dia->setTimezone(new DateTimeZone(self::FUSO))->modify('first day of previous month');

        return new self((int) $local->format('Y'), (int) $local->format('n'));
    }

    /** Primeiro instante do mês, em UTC. */
    public function inicio(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->ano, $this->mes), new DateTimeZone(self::FUSO))
            ->setTimezone(new DateTimeZone('UTC'));
    }

    /** Primeiro instante do mês seguinte, em UTC (limite exclusivo). */
    public function fim(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->ano, $this->mes), new DateTimeZone(self::FUSO))
            ->modify('first day of next month')->setTimezone(new DateTimeZone('UTC'));
    }

    public function valor(): string
    {
        return sprintf('%04d-%02d', $this->ano, $this->mes);
    }

    /** 11/2026 */
    public function rotulo(): string
    {
        return sprintf('%02d/%04d', $this->mes, $this->ano);
    }
}

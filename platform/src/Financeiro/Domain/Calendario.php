<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Domain;

use DateTimeImmutable;
use DateTimeZone;

/** As datas do fechamento, no horário do laboratório. Dia 29 a 31 em mês curto vale o último dia do mês. */
final class Calendario
{
    private const string FUSO = 'America/Sao_Paulo';

    public static function fechaNoDia(int $diaDeFechamento, DateTimeImmutable $hoje): bool
    {
        $local = $hoje->setTimezone(new DateTimeZone(self::FUSO));

        return (int) $local->format('j') === min($diaDeFechamento, (int) $local->format('t'));
    }

    /** O vencimento cai no mês do fechamento; se o dia já passou ou é o mesmo do fechamento, vai para o mês seguinte. */
    public static function vencimento(int $diaDeFechamento, int $diaDeVencimento, DateTimeImmutable $fechamento): DateTimeImmutable
    {
        $local = $fechamento->setTimezone(new DateTimeZone(self::FUSO));
        $fechouNoDia = min($diaDeFechamento, (int) $local->format('t'));
        $mes = $local->modify('first day of this month');
        if ($diaDeVencimento <= $fechouNoDia) {
            $mes = $mes->modify('first day of next month');
        }
        $dia = min($diaDeVencimento, (int) $mes->format('t'));

        // Meio-dia: a data não muda ao passar por UTC.
        return $mes->setDate((int) $mes->format('Y'), (int) $mes->format('n'), $dia)->setTime(12, 0);
    }
}

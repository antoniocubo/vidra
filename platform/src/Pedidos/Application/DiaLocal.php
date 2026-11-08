<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application;

use DateTimeImmutable;
use DateTimeZone;

/** Um dia do relógio de São Paulo, com o intervalo UTC que ele cobre. */
final readonly class DiaLocal
{
    private function __construct(public string $data, public DateTimeImmutable $inicio, public DateTimeImmutable $fim) {}

    /** Sem data, ou com uma data inválida, vale o dia de hoje. */
    public static function de(?string $data, DateTimeImmutable $agora): self
    {
        $saoPaulo = new DateTimeZone('America/Sao_Paulo');
        $dia = $data === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $data, $saoPaulo);
        if ($dia === false || $dia->format('Y-m-d') !== $data) {
            $dia = $agora->setTimezone($saoPaulo)->setTime(0, 0);
        }

        $utc = new DateTimeZone('UTC');

        return new self($dia->format('Y-m-d'), $dia->setTimezone($utc), $dia->modify('+1 day')->setTimezone($utc));
    }
}

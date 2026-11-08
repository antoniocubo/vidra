<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application;

use DateTimeImmutable;
use DateTimeZone;

/** Datas gravadas em UTC, mostradas em America/Sao_Paulo. */
final readonly class Formato
{
    public static function hora(DateTimeImmutable $data): string
    {
        return self::local($data)->format('H:i');
    }

    public static function dataEHora(DateTimeImmutable $data): string
    {
        return self::local($data)->format('d/m/Y H:i');
    }

    public static function local(DateTimeImmutable $data): DateTimeImmutable
    {
        return $data->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    }
}

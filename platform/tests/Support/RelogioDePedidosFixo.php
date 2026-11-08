<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Pedidos\Domain\Relogio;

final readonly class RelogioDePedidosFixo implements Relogio
{
    public function __construct(private DateTimeImmutable $data = new DateTimeImmutable('2026-11-08 13:35:00 UTC')) {}

    public function agora(): DateTimeImmutable
    {
        return $this->data;
    }
}

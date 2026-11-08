<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application;

use DateTimeImmutable;
use Vidra\Pedidos\Domain\Relogio;

final readonly class HoraDoLaboratorio
{
    public function __construct(private Relogio $relogio) {}

    public function __invoke(): DateTimeImmutable
    {
        return $this->relogio->agora();
    }
}

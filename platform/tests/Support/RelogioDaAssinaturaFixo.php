<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Acesso\Domain\Relogio;

final class RelogioDaAssinaturaFixo implements Relogio
{
    public function __construct(public DateTimeImmutable $agora) {}

    public function agora(): DateTimeImmutable
    {
        return $this->agora;
    }
}

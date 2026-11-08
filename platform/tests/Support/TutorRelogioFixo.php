<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Vidra\Veterinaria\Domain\Relogio;

final readonly class TutorRelogioFixo implements Relogio
{
    public function __construct(private DateTimeImmutable $data = new DateTimeImmutable('2026-11-08 17:32:00 UTC')) {}

    public function agora(): DateTimeImmutable
    {
        return $this->data;
    }
}

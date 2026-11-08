<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Tutor;

use DateTimeImmutable;
use DateTimeZone;

final readonly class AutorizacaoDeEmail
{
    public DateTimeImmutable $registradaEm;

    public function __construct(DateTimeImmutable $registradaEm, public int $registradaPor)
    {
        $this->registradaEm = $registradaEm->setTimezone(new DateTimeZone('UTC'));
    }
}

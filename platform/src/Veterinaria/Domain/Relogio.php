<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain;

use DateTimeImmutable;

interface Relogio
{
    public function agora(): DateTimeImmutable;
}

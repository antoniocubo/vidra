<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use DateTimeImmutable;

interface Relogio
{
    public function agora(): DateTimeImmutable;
}

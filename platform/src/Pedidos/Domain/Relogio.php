<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain;

use DateTimeImmutable;

interface Relogio
{
    public function agora(): DateTimeImmutable;
}

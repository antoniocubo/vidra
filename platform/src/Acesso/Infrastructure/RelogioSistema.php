<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure;

use DateTimeImmutable;
use Illuminate\Support\Facades\Date;
use Vidra\Acesso\Domain\Relogio;

final class RelogioSistema implements Relogio
{
    public function agora(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface(Date::now('UTC'));
    }
}

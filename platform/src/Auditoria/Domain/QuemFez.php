<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Domain;

use DateTimeImmutable;

interface QuemFez
{
    public function usuarioId(): ?int;

    public function nome(): string;

    public function ip(): ?string;

    public function agora(): DateTimeImmutable;
}

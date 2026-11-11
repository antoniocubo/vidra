<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Vidra\Auditoria\Domain\QuemFez;

final class QuemFezNaSessao implements QuemFez
{
    public function usuarioId(): ?int
    {
        $id = Auth::guard('web')->id();

        return $id === null ? null : (int) $id;
    }

    public function nome(): string
    {
        return (string) (Auth::guard('web')->user()?->getAttribute('name') ?? 'Sistema');
    }

    public function ip(): ?string
    {
        return request()->ip();
    }

    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}

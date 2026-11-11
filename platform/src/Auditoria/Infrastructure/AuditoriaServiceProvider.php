<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Auditoria\Domain\QuemFez;
use Vidra\Auditoria\Domain\TrilhaDeAuditoria;
use Vidra\Auditoria\Infrastructure\Persistence\EloquentTrilhaDeAuditoria;

final class AuditoriaServiceProvider extends ServiceProvider
{
    /** @var array<class-string,class-string> */
    public array $bindings = [QuemFez::class => QuemFezNaSessao::class, TrilhaDeAuditoria::class => EloquentTrilhaDeAuditoria::class];
}

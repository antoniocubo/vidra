<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ContextoLaboratorio::class, static fn (Application $app): ContextoLaboratorio => new ContextoLaboratorio($app->make('db')->connection()));
        $this->app->alias(ContextoLaboratorio::class, LaboratorioAtual::class);
    }
}

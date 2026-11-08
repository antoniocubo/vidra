<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Vidra\Laudos\Domain\PreferenciaDeEntrega;
use Vidra\Veterinaria\Infrastructure\Laudos\PreferenciaDeEntregaVeterinaria;

final class EntregaLaudosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PreferenciaDeEntrega::class, PreferenciaDeEntregaVeterinaria::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('laudo-publico', fn (\Illuminate\Http\Request $r) => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($r->ip() ?? ''));
    }
}

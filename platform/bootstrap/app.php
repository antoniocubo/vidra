<?php

declare(strict_types=1);

use App\Http\Middleware\DefinirLaboratorioAtual;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'laboratorio' => DefinirLaboratorioAtual::class,
            'area' => App\Http\Middleware\ExigirArea::class,
            'laboratorio.vet' => App\Http\Middleware\DefinirLaboratorioDoVeterinario::class,
        ]);
        // O acesso vet tem login próprio; o resto vai para o login da equipe.
        $middleware->redirectGuestsTo(fn (Illuminate\Http\Request $request): string => $request->routeIs('vet.*') ? route('vet.entrar') : route('login'));
        $middleware->redirectUsersTo(fn (Illuminate\Http\Request $request): string => $request->routeIs('vet.*') ? route('vet.inicio') : route('dashboard'));
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['senha']);

        if (config('sentry.dsn')) {
            Sentry\Laravel\Integration::handles($exceptions);
        }
    })->create();

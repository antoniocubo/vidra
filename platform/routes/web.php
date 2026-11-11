<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\PainelController;
use App\Http\Controllers\Site\InicioController;
use App\Http\Controllers\Site\PrivacidadeController;
use App\Http\Controllers\Site\TermosController;
use App\Http\Controllers\Webhooks\AsaasController;
use Illuminate\Support\Facades\Route;

/*
| Cada superfície no seu host em produção (config vidra.dominios). Sem host
| configurado, tudo responde no host atual e vet/laudo ganham um prefixo.
*/
$superficie = static function (string $nome, ?string $prefixoLocal, Closure $rotas): void {
    $dominio = config("vidra.dominios.{$nome}");
    Route::group(is_string($dominio) && $dominio !== '' ? ['domain' => $dominio] : ['prefix' => $prefixoLocal], $rotas);
};

$superficie('site', null, function (): void {
    Route::get('/', InicioController::class)->name('home');
    Route::get('privacidade', PrivacidadeController::class)->name('site.privacidade');
    Route::get('termos', TermosController::class)->name('site.termos');
});

$superficie('app', null, function (): void {
    if (config('vidra.dominios.app')) {
        Route::redirect('/', '/dashboard');
    }
    Route::post('webhooks/asaas', AsaasController::class)->name('webhooks.asaas');

    Route::middleware(['auth', 'laboratorio'])->group(function (): void {
        Route::get('dashboard', PainelController::class)->name('dashboard');
    });

    require __DIR__.'/lab.php';

    if (app()->environment(['local', 'testing'])) {
        require __DIR__.'/dev.php';
    }

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
    require __DIR__.'/acesso.php';
});

$superficie('laudo', 'laudo', fn () => require __DIR__.'/laudo.php');
$superficie('vet', 'vet', fn () => require __DIR__.'/vet.php');

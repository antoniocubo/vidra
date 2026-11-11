<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\PainelController;
use App\Http\Controllers\Site\InicioController;
use App\Http\Controllers\Site\PrivacidadeController;
use App\Http\Controllers\Site\TermosController;
use App\Http\Controllers\Webhooks\AsaasController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('home');
Route::get('privacidade', PrivacidadeController::class)->name('site.privacidade');
Route::post('webhooks/asaas', AsaasController::class)->name('webhooks.asaas');
Route::get('termos', TermosController::class)->name('site.termos');

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

require __DIR__.'/laudo.php';
require __DIR__.'/vet.php';

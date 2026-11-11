<?php

declare(strict_types=1);

use App\Http\Controllers\Acesso\CadastroController;
use App\Http\Controllers\Acesso\LaboratorioAtualController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [CadastroController::class, 'create'])->name('cadastro');
    Route::post('/cadastro', [CadastroController::class, 'store'])->middleware('throttle:10,1')->name('cadastro.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/laboratorio', [LaboratorioAtualController::class, 'create'])->name('laboratorio.escolher');
    Route::post('/laboratorio', [LaboratorioAtualController::class, 'store'])->name('laboratorio.entrar');
});

Route::middleware('throttle:10,1')->group(function (): void {
    Route::get('/convite/{token}', [App\Http\Controllers\Acesso\ConviteController::class, 'show'])->name('convite.show');
    Route::post('/convite/{token}', [App\Http\Controllers\Acesso\ConviteController::class, 'store'])->name('convite.aceitar');
});

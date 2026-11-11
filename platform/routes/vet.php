<?php

declare(strict_types=1);

use App\Http\Controllers\Vet\LaboratorioController;
use App\Http\Controllers\Vet\SessaoController;
use Illuminate\Support\Facades\Route;

/*
| Acesso do veterinário (vet.vidralab.com.br). Por enquanto sob /vet; o lote de
| subdomínios troca o prefixo por Route::domain(). Guard 'vet', nunca o 'web'.
*/

Route::prefix('vet')->name('vet.')->group(function (): void {
    Route::middleware('guest:vet')->group(function (): void {
        Route::get('entrar', [SessaoController::class, 'create'])->name('entrar');
        Route::post('entrar', [SessaoController::class, 'store'])->middleware('throttle:10,1')->name('entrar.store');
    });

    Route::middleware('auth:vet')->group(function (): void {
        Route::post('sair', [SessaoController::class, 'destroy'])->name('sair');
        Route::get('laboratorio', [LaboratorioController::class, 'create'])->name('laboratorio.escolher');
        Route::post('laboratorio', [LaboratorioController::class, 'store'])->name('laboratorio.entrar');

        Route::middleware('laboratorio.vet')->group(function (): void {
            // Telas do veterinário (lista de pedidos e laudos): lote L6.
            Route::get('/', fn () => Inertia\Inertia::render('vet/inicio'))->name('inicio');
        });
    });
});

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
    Route::get('convite/{token}', [App\Http\Controllers\Vet\ConviteController::class, 'show'])->middleware('throttle:20,1')->name('convite.show');
    Route::post('convite/{token}', [App\Http\Controllers\Vet\ConviteController::class, 'store'])->middleware('throttle:5,1')->name('convite.store');
    Route::middleware('guest:vet')->group(function (): void {
        Route::get('senha/esqueci', [App\Http\Controllers\Vet\SenhaController::class, 'esqueci'])->name('senha.esqueci');
        Route::post('senha/esqueci', [App\Http\Controllers\Vet\SenhaController::class, 'enviar'])->middleware('throttle:5,1')->name('senha.enviar');
        Route::get('senha/nova/{token}', [App\Http\Controllers\Vet\SenhaController::class, 'nova'])->name('senha.nova');
        Route::post('senha/nova/{token}', [App\Http\Controllers\Vet\SenhaController::class, 'salvar'])->middleware('throttle:5,1')->name('senha.salvar');
        Route::get('entrar', [SessaoController::class, 'create'])->name('entrar');
        Route::post('entrar', [SessaoController::class, 'store'])->middleware('throttle:10,1')->name('entrar.store');
    });

    Route::middleware('auth:vet')->group(function (): void {
        Route::post('sair', [SessaoController::class, 'destroy'])->name('sair');
        Route::get('laboratorio', [App\Http\Controllers\Vet\PedidoController::class, 'escolher'])->name('laboratorio.escolher');
        Route::post('laboratorio', [LaboratorioController::class, 'store'])->name('laboratorio.entrar');

        Route::middleware('laboratorio.vet')->group(function (): void {
            Route::get('/', [App\Http\Controllers\Vet\PedidoController::class, 'index'])->name('inicio');
            Route::get('pedidos/{pedido}/laudo.pdf', [App\Http\Controllers\Vet\PedidoController::class, 'pdf'])->whereUuid('pedido')->middleware('throttle:30,1')->name('laudo.pdf');
        });
    });
});

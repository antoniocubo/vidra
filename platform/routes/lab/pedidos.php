<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\AmostraController;
use App\Http\Controllers\Lab\PedidoController;
use Illuminate\Support\Facades\Route;

/* Núcleo: pedidos, amostras e etiquetas. Carregado dentro do grupo de routes/lab.php. */

Route::middleware('area:pedidos')->group(function (): void {
    Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('pedidos/novo', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->whereUuid('pedido')->name('pedidos.show');
    Route::get('pedidos/{pedido}/etiquetas', [PedidoController::class, 'etiquetas'])->whereUuid('pedido')->name('pedidos.etiquetas');
});

Route::middleware('area:amostras')->group(function (): void {
    Route::get('amostras', [AmostraController::class, 'index'])->name('amostras.index');
    Route::post('amostras/receber', [AmostraController::class, 'receber'])->name('amostras.receber');
    Route::post('amostras/{amostra}/rejeitar', [AmostraController::class, 'rejeitar'])->whereUuid('amostra')->name('amostras.rejeitar');
    Route::post('amostras/{amostra}/ressalva', [AmostraController::class, 'aceitarComRessalva'])->whereUuid('amostra')->name('amostras.ressalva');
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\LiberacaoController;
use Illuminate\Support\Facades\Route;

Route::get('liberacao', [LiberacaoController::class, 'index'])->name('liberacao.index');
Route::post('liberacao/{pedido}/avisos', [LiberacaoController::class, 'aviso'])->whereUuid('pedido')->name('liberacao.aviso');
Route::post('liberacao/{pedido}/liberar', [LiberacaoController::class, 'liberar'])->whereUuid('pedido')->name('liberacao.liberar');

Route::get('pedidos/{pedido}/laudo.pdf', [App\Http\Controllers\Lab\EntregaLaudoController::class, 'pdf'])->whereUuid('pedido')->name('pedidos.laudo.pdf');
Route::post('pedidos/{pedido}/laudo/reenviar', [App\Http\Controllers\Lab\EntregaLaudoController::class, 'reenviar'])->whereUuid('pedido')->middleware('throttle:5,1')->name('pedidos.laudo.reenviar');

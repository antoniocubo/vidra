<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\LiberacaoController;
use Illuminate\Support\Facades\Route;

Route::get('liberacao', [LiberacaoController::class, 'index'])->name('liberacao.index');
Route::post('liberacao/{pedido}/avisos', [LiberacaoController::class, 'aviso'])->whereUuid('pedido')->name('liberacao.aviso');
Route::post('liberacao/{pedido}/liberar', [LiberacaoController::class, 'liberar'])->whereUuid('pedido')->name('liberacao.liberar');

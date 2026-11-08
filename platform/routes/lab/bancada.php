<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\BancadaController;
use Illuminate\Support\Facades\Route;

Route::get('bancada', [BancadaController::class, 'index'])->name('bancada.index');
Route::post('bancada/{item}/resultados', [BancadaController::class, 'salvar'])->whereUuid('item')->name('bancada.salvar');
Route::post('bancada/{item}/conferencia', [BancadaController::class, 'conferir'])->whereUuid('item')->name('bancada.conferir');
Route::post('bancada/{item}/reabrir', [BancadaController::class, 'reabrir'])->whereUuid('item')->name('bancada.reabrir');

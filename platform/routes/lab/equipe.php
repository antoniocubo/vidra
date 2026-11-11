<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\EquipeController;
use Illuminate\Support\Facades\Route;

Route::get('/equipe', [EquipeController::class, 'index'])->name('equipe.index');
Route::post('/equipe/convites', [EquipeController::class, 'store'])->name('equipe.convidar');
Route::post('/equipe/convites/{convite}/reenviar', [EquipeController::class, 'reenviar'])->whereUuid('convite')->name('equipe.reenviar');
Route::delete('/equipe/convites/{convite}', [EquipeController::class, 'cancelar'])->whereUuid('convite')->name('equipe.cancelar');
Route::patch('/equipe/{usuario}/papel', [EquipeController::class, 'trocar'])->whereNumber('usuario')->name('equipe.papel');
Route::delete('/equipe/{usuario}', [EquipeController::class, 'remover'])->whereNumber('usuario')->name('equipe.remover');

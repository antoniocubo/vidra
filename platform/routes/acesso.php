<?php

declare(strict_types=1);

use App\Http\Controllers\Acesso\CadastroController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [CadastroController::class, 'create'])->name('cadastro');
    Route::post('/cadastro', [CadastroController::class, 'store'])->middleware('throttle:10,1')->name('cadastro.store');
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\VeterinarioController;
use Illuminate\Support\Facades\Route;

/*
| Portal do laboratório (app.vidralab.com.br). Quando o roteamento por subdomínio
| entrar, este arquivo passa a ser carregado dentro de Route::domain().
*/

Route::middleware(['auth', 'laboratorio'])->name('lab.')->group(function (): void {
    Route::get('veterinarios/novo', [VeterinarioController::class, 'create'])->name('veterinarios.create');
    Route::post('veterinarios', [VeterinarioController::class, 'store'])->name('veterinarios.store');
});

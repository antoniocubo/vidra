<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\AssinaturaController;
use Illuminate\Support\Facades\Route;

Route::get('/assinatura', [AssinaturaController::class, 'index'])->name('assinatura.index');
Route::post('/assinatura', [AssinaturaController::class, 'store'])->name('assinatura.store');

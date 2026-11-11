<?php

declare(strict_types=1);
use App\Http\Controllers\Laudo\LaudoController;
use App\Http\Middleware\PrivacidadeDoLaudo;
use Illuminate\Support\Facades\Route;

Route::prefix('laudo')->name('laudo.')->middleware([PrivacidadeDoLaudo::class, 'throttle:laudo-publico'])->group(function (): void {
    Route::get('/', [LaudoController::class, 'acesso'])->name('acesso');
    Route::get('{codigo}/versoes/{versao}.pdf', [LaudoController::class, 'pdf'])->whereNumber('versao')->name('versao.pdf');
    Route::get('{codigo}/pdf', [LaudoController::class, 'pdf'])->name('pdf');
    Route::get('{codigo}', [LaudoController::class, 'show'])->name('show');
});

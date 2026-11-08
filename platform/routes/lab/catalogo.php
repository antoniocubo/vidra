<?php

declare(strict_types=1);
use App\Http\Controllers\Lab\ExameController;
use Illuminate\Support\Facades\Route;

Route::get('exames', [ExameController::class, 'index'])->name('exames.index');
Route::get('exames/novo', [ExameController::class, 'create'])->name('exames.create');
Route::post('exames', [ExameController::class, 'store'])->name('exames.store');
Route::get('exames/{exame}/editar', [ExameController::class, 'edit'])->whereUuid('exame')->name('exames.edit');
Route::put('exames/{exame}', [ExameController::class, 'update'])->whereUuid('exame')->name('exames.update');
Route::get('exames/{exame}/referencias', [ExameController::class, 'referencias'])->whereUuid('exame')->name('exames.referencias');
Route::put('exames/{exame}/referencias', [ExameController::class, 'salvarReferencias'])->whereUuid('exame')->name('exames.referencias.update');
Route::post('exames/{exame}/desativar', [ExameController::class, 'desativar'])->whereUuid('exame')->name('exames.desativar');
Route::post('exames/{exame}/reativar', [ExameController::class, 'reativar'])->whereUuid('exame')->name('exames.reativar');

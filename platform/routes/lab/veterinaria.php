<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\VeterinarioController;
use Illuminate\Support\Facades\Route;

/* Linha Pet: veterinários, tutores, animais e clínicas. Carregado dentro do grupo de routes/lab.php. */

Route::get('veterinarios/novo', [VeterinarioController::class, 'create'])->name('veterinarios.create');
Route::post('veterinarios', [VeterinarioController::class, 'store'])->name('veterinarios.store');

Route::get('tutores', [App\Http\Controllers\Lab\TutorController::class, 'index'])->name('tutores.index');
Route::get('tutores/novo', [App\Http\Controllers\Lab\TutorController::class, 'create'])->name('tutores.create');
Route::post('tutores', [App\Http\Controllers\Lab\TutorController::class, 'store'])->name('tutores.store');
Route::get('tutores/{tutor}/editar', [App\Http\Controllers\Lab\TutorController::class, 'edit'])->whereUuid('tutor')->name('tutores.edit');
Route::put('tutores/{tutor}/editar', [App\Http\Controllers\Lab\TutorController::class, 'update'])->whereUuid('tutor')->name('tutores.update');
Route::post('tutores/{tutor}/autorizacao', [App\Http\Controllers\Lab\TutorController::class, 'autorizar'])->whereUuid('tutor')->name('tutores.autorizacao.store');
Route::delete('tutores/{tutor}/autorizacao', [App\Http\Controllers\Lab\TutorController::class, 'retirar'])->whereUuid('tutor')->name('tutores.autorizacao.destroy');
Route::get('tutores/{tutor}/animais/novo', [App\Http\Controllers\Lab\AnimalController::class, 'create'])->whereUuid('tutor')->name('tutores.animais.create');
Route::post('tutores/{tutor}/animais/novo', [App\Http\Controllers\Lab\AnimalController::class, 'store'])->whereUuid('tutor')->name('tutores.animais.store');

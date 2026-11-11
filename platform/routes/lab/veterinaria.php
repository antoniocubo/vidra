<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\ClinicaController;
use App\Http\Controllers\Lab\VeterinarioController;
use Illuminate\Support\Facades\Route;

/* Linha Pet: veterinários, tutores, animais e clínicas. Carregado dentro do grupo de routes/lab.php. */

Route::get('veterinarios', [VeterinarioController::class, 'index'])->name('veterinarios.index');
Route::get('veterinarios/novo', [VeterinarioController::class, 'create'])->name('veterinarios.create');
Route::post('veterinarios', [VeterinarioController::class, 'store'])->name('veterinarios.store');
Route::get('veterinarios/{vinculo}/editar', [VeterinarioController::class, 'edit'])->whereUuid('vinculo')->name('veterinarios.edit');
Route::put('veterinarios/{vinculo}/editar', [VeterinarioController::class, 'update'])->whereUuid('vinculo')->name('veterinarios.update');

Route::get('clinicas', [ClinicaController::class, 'index'])->name('clinicas.index');
Route::get('clinicas/nova', [ClinicaController::class, 'create'])->name('clinicas.create');
Route::post('clinicas', [ClinicaController::class, 'store'])->name('clinicas.store');
Route::get('clinicas/{clinica}/editar', [ClinicaController::class, 'edit'])->whereUuid('clinica')->name('clinicas.edit');
Route::put('clinicas/{clinica}/editar', [ClinicaController::class, 'update'])->whereUuid('clinica')->name('clinicas.update');
Route::post('clinicas/{clinica}/desativar', [ClinicaController::class, 'desativar'])->whereUuid('clinica')->name('clinicas.desativar');
Route::post('clinicas/{clinica}/reativar', [ClinicaController::class, 'reativar'])->whereUuid('clinica')->name('clinicas.reativar');

Route::get('tutores', [App\Http\Controllers\Lab\TutorController::class, 'index'])->name('tutores.index');
Route::get('tutores/novo', [App\Http\Controllers\Lab\TutorController::class, 'create'])->name('tutores.create');
Route::post('tutores', [App\Http\Controllers\Lab\TutorController::class, 'store'])->name('tutores.store');
Route::get('tutores/{tutor}/editar', [App\Http\Controllers\Lab\TutorController::class, 'edit'])->whereUuid('tutor')->name('tutores.edit');
Route::put('tutores/{tutor}/editar', [App\Http\Controllers\Lab\TutorController::class, 'update'])->whereUuid('tutor')->name('tutores.update');
Route::post('tutores/{tutor}/autorizacao', [App\Http\Controllers\Lab\TutorController::class, 'autorizar'])->whereUuid('tutor')->name('tutores.autorizacao.store');
Route::delete('tutores/{tutor}/autorizacao', [App\Http\Controllers\Lab\TutorController::class, 'retirar'])->whereUuid('tutor')->name('tutores.autorizacao.destroy');
Route::get('tutores/{tutor}/animais/novo', [App\Http\Controllers\Lab\AnimalController::class, 'create'])->whereUuid('tutor')->name('tutores.animais.create');
Route::post('tutores/{tutor}/animais/novo', [App\Http\Controllers\Lab\AnimalController::class, 'store'])->whereUuid('tutor')->name('tutores.animais.store');

Route::post('veterinarios/{vinculo}/acesso', [App\Http\Controllers\Vet\ConviteController::class, 'enviar'])->whereUuid('vinculo')->middleware('throttle:5,1')->name('veterinarios.acesso');

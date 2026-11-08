<?php

declare(strict_types=1);

use App\Http\Controllers\Site\InicioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', InicioController::class)->name('home');

Route::middleware(['auth', 'laboratorio'])->group(function (): void {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/lab.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/dev.php';
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/acesso.php';

require __DIR__.'/laudo.php';

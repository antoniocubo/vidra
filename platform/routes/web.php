<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function (): void {
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

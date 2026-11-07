<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| Rotas só de desenvolvimento. Carregadas apenas em local e testing (ver routes/web.php).
*/

Route::get('dev/componentes', fn () => Inertia::render('dev/componentes'))->name('dev.componentes');

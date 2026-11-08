<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Portal do laboratório (app.vidralab.com.br). Quando o roteamento por subdomínio
| entrar, este arquivo passa a ser carregado dentro de Route::domain().
| Cada módulo tem o seu arquivo em routes/lab/.
*/

Route::middleware(['auth', 'laboratorio'])->name('lab.')->group(function (): void {
    require __DIR__.'/lab/veterinaria.php';
    require __DIR__.'/lab/catalogo.php';
    require __DIR__.'/lab/pedidos.php';
});

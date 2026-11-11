<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Portal do laboratório (app.vidralab.com.br). Quando o roteamento por subdomínio
| entrar, este arquivo passa a ser carregado dentro de Route::domain().
| Cada módulo tem o seu arquivo em routes/lab/.
*/

Route::middleware(['auth', 'laboratorio'])->name('lab.')->group(function (): void {
    // Cada grupo exige uma área do papel (Vidra\Acesso\Domain\Papel).
    Route::middleware('area:cadastros')->group(__DIR__.'/lab/veterinaria.php');
    Route::middleware('area:exames')->group(__DIR__.'/lab/catalogo.php');
    require __DIR__.'/lab/pedidos.php';
    Route::middleware('area:bancada')->group(__DIR__.'/lab/bancada.php');
    require __DIR__.'/lab/laudos.php';
    Route::middleware('area:financeiro')->group(__DIR__.'/lab/financeiro.php');
    Route::middleware('area:equipe')->group(__DIR__.'/lab/equipe.php');
    Route::middleware('area:assinatura')->group(__DIR__.'/lab/assinatura.php');
});

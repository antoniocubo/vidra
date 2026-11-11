<?php

declare(strict_types=1);

use App\Http\Controllers\Lab\FinanceiroController;
use Illuminate\Support\Facades\Route;

Route::get('financeiro', [FinanceiroController::class, 'index'])->name('financeiro.index');
Route::post('financeiro/fechar', [FinanceiroController::class, 'fechar'])->name('financeiro.fechar');
Route::get('financeiro/faturas/{fatura}.pdf', [FinanceiroController::class, 'pdf'])->whereUuid('fatura')->name('financeiro.pdf');
Route::post('financeiro/faturas/{fatura}/reenviar', [FinanceiroController::class, 'reenviar'])->whereUuid('fatura')->middleware('throttle:5,1')->name('financeiro.reenviar');
Route::post('financeiro/faturas/{fatura}/pagamento', [FinanceiroController::class, 'pagamento'])->whereUuid('fatura')->name('financeiro.pagamento');
Route::post('financeiro/faturas/{fatura}/creditos', [FinanceiroController::class, 'credito'])->whereUuid('fatura')->name('financeiro.credito');

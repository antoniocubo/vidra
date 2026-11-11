<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('vidra:fechar-faturas')->dailyAt('06:00')->timezone('America/Sao_Paulo');
Schedule::command('vidra:cobrar-excedentes')->monthlyOn(1, '07:00')->timezone('America/Sao_Paulo');
Schedule::command('vidra:avisar-vencimento')->dailyAt('09:00')->timezone('America/Sao_Paulo');

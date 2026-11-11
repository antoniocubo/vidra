<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| O "resumo do dia, às 18h" ainda não existe: quem o escolheu não recebia laudo
| nenhum. Até ele existir, esses vínculos recebem cada laudo na hora.
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::table('laboratorio_veterinario')->where('recebe_laudo', 'resumo_diario')->update(['recebe_laudo' => 'cada_laudo']);
    }

    public function down(): void {}
};

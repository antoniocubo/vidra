<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table): void {
            $table->string('cancelado_motivo', 500)->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users');
            $table->timestampTz('cancelado_em')->nullable();
            $table->dropUnique(['pedido_id', 'exame_id']);
        });
        // O histórico cancelado permanece quando o mesmo exame é solicitado novamente.
        DB::statement("CREATE UNIQUE INDEX pedido_itens_pedido_id_exame_id_unique ON pedido_itens (pedido_id, exame_id) WHERE situacao <> 'cancelado'");
    }

    public function down(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table): void {
            $table->dropUnique(['pedido_id', 'exame_id']);
            $table->dropForeign(['cancelado_por']);
            $table->dropColumn(['cancelado_motivo', 'cancelado_por', 'cancelado_em']);
            $table->unique(['pedido_id', 'exame_id']);
        });
    }
};

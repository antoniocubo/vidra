<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Vidra\Shared\Infrastructure\Tenancy\Rls;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_auditoria', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('laboratorio_id');
            $table->string('acao');
            $table->string('alvo_tipo');
            $table->string('alvo_id');
            $table->uuid('pedido_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nome');
            $table->text('detalhe');
            $table->string('ip', 45)->nullable();
            $table->timestampTz('em', 6);
            $table->index(['laboratorio_id', 'pedido_id', 'em']);
            $table->index(['laboratorio_id', 'alvo_tipo', 'em']);
        });
        Rls::isolarPorLaboratorio('registros_auditoria');
        if (DB::getDriverName() === 'pgsql' && DB::selectOne("select 1 from pg_roles where rolname = 'vidra_app'") !== null) {
            DB::statement('revoke update, delete on registros_auditoria from vidra_app');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
    }
};

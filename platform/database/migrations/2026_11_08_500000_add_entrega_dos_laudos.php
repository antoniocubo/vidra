<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vidra\Shared\Infrastructure\Tenancy\Rls;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laudos', function (Blueprint $t): void {
            $t->string('pdf_caminho')->nullable();
            $t->timestampTz('pdf_gerado_em')->nullable();
            $t->unsignedInteger('pdf_tentativas')->default(0);
            $t->timestampTz('pdf_tentado_em')->nullable();
            $t->text('pdf_erro')->nullable();
            // A superfície pública precisa de uma chave inequívoca entre laboratórios.
            $t->unique('codigo_acesso', 'laudos_codigo_publico_unico');
        });
        Schema::create('entregas_laudo', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('laudo_id')->constrained('laudos')->cascadeOnDelete();
            $t->uuid('envio_id');
            $t->string('canal', 20);
            $t->string('destinatario');
            $t->string('papel', 20);
            $t->string('situacao', 20);
            $t->timestampTz('quando');
            $t->text('erro')->nullable();
            $t->index(['laboratorio_id', 'laudo_id']);
        });
        Rls::isolarPorLaboratorio('entregas_laudo');
        Schema::create('acessos_laudo', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('laudo_id')->constrained('laudos')->cascadeOnDelete();
            $t->timestampTz('quando');
            $t->string('ip', 45);
            $t->string('user_agent', 200);
            $t->index(['laboratorio_id', 'laudo_id']);
        });
        Rls::isolarPorLaboratorio('acessos_laudo');
    }

    public function down(): void
    {
        Schema::dropIfExists('acessos_laudo');
        Schema::dropIfExists('entregas_laudo');
        Schema::table('laudos', function (Blueprint $t): void {
            $t->dropUnique('laudos_codigo_publico_unico');
            $t->dropColumn(['pdf_caminho', 'pdf_gerado_em', 'pdf_tentativas', 'pdf_tentado_em', 'pdf_erro']);
        });
    }
};

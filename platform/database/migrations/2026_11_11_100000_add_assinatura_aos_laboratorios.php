<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratorios', function (Blueprint $table): void {
            $table->timestampTz('pago_ate')->nullable();
            $table->string('asaas_cliente_id')->nullable()->index();
            $table->string('asaas_assinatura_id')->nullable();
            $table->date('assinatura_avisada_em')->nullable();
        });

        // Sem RLS de propósito: o webhook do Asaas escreve sem laboratório atual (ver TABELAS_SEM_RLS no teste de RLS).
        Schema::create('cobrancas_vidra', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('asaas_pagamento_id')->unique();
            $table->string('tipo', 20);
            $table->char('competencia', 7)->nullable();
            $table->unsignedInteger('valor_centavos');
            $table->string('situacao', 20);
            $table->date('vencimento');
            $table->string('link_pagamento')->nullable();
            $table->timestampTz('paga_em')->nullable();
            $table->timestamps();
            $table->index(['laboratorio_id', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas_vidra');
        Schema::table('laboratorios', function (Blueprint $table): void {
            $table->dropColumn(['pago_ate', 'asaas_cliente_id', 'asaas_assinatura_id', 'assinatura_avisada_em']);
        });
    }
};

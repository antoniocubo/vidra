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
        Schema::create('faturas', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->unsignedBigInteger('numero');
            // O pagador é da linha de produto (clínica ou vínculo do veterinário): sem chave estrangeira de propósito.
            $t->string('pagador_tipo', 20);
            $t->uuid('pagador_id');
            $t->string('pagador_nome');
            $t->string('pagador_email')->nullable();
            $t->char('competencia', 7);
            $t->date('vencimento');
            $t->timestampTz('fechada_em');
            $t->timestampTz('enviada_em')->nullable();
            $t->timestampTz('paga_em')->nullable();
            $t->unsignedBigInteger('paga_por')->nullable();
            $t->string('pdf_caminho')->nullable();
            $t->timestampsTz();
            $t->unique(['laboratorio_id', 'numero']);
            $t->unique(['laboratorio_id', 'pagador_tipo', 'pagador_id', 'competencia']);
            $t->index(['laboratorio_id', 'competencia']);
        });
        Rls::isolarPorLaboratorio('faturas');

        Schema::create('linhas_fatura', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('fatura_id')->constrained('faturas')->cascadeOnDelete();
            // Um pedido nunca entra em duas faturas.
            $t->foreignUuid('pedido_id')->unique()->constrained('pedidos')->cascadeOnDelete();
            $t->string('protocolo', 20);
            $t->string('paciente');
            $t->timestampTz('liberado_em');
            $t->text('exames');
            $t->unsignedBigInteger('valor_centavos');
            $t->index('fatura_id');
        });
        Rls::isolarPorLaboratorio('linhas_fatura');

        Schema::create('creditos_fatura', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('fatura_id')->constrained('faturas')->cascadeOnDelete();
            $t->string('motivo', 500);
            $t->unsignedBigInteger('valor_centavos');
            $t->unsignedBigInteger('por');
            $t->timestampTz('em');
            $t->index('fatura_id');
        });
        Rls::isolarPorLaboratorio('creditos_fatura');
    }

    public function down(): void
    {
        Schema::dropIfExists('creditos_fatura');
        Schema::dropIfExists('linhas_fatura');
        Schema::dropIfExists('faturas');
    }
};

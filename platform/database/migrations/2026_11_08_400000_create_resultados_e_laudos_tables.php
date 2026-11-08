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
        Schema::create('resultados', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignUuid('item_id')->constrained('pedido_itens')->cascadeOnDelete();
            $t->uuid('paciente_id');
            $t->uuid('analito_id');
            $t->string('nome');
            $t->string('unidade')->nullable();
            $t->string('valor')->nullable();
            $t->text('texto')->nullable();
            $t->unsignedBigInteger('digitado_por');
            $t->string('digitador_nome');
            $t->timestampTz('digitado_em', 6);
            $t->string('perfil')->nullable();
            foreach (['minimo', 'maximo', 'critico_baixo', 'critico_alto'] as $c) {
                $t->string($c)->nullable();
            }
            $t->string('classificacao', 30);
            $t->timestampsTz();
            $t->unique(['item_id', 'analito_id']);
            $t->index(['laboratorio_id', 'paciente_id', 'analito_id']);
        });
        Rls::isolarPorLaboratorio('resultados');
        Schema::create('laudos', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->unsignedBigInteger('numero');
            $t->unsignedBigInteger('liberado_por');
            $t->string('usuario_nome');
            $t->string('rt_nome');
            $t->string('rt_registro');
            $t->timestampTz('liberado_em');
            $t->text('observacao')->nullable();
            $t->string('codigo_acesso', 9);
            $t->timestampsTz();
            $t->unique(['laboratorio_id', 'numero']);
            $t->unique(['laboratorio_id', 'codigo_acesso']);
            $t->unique('pedido_id');
        });
        Rls::isolarPorLaboratorio('laudos');
        Schema::create('avisos_valor_critico', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignUuid('resultado_id')->constrained('resultados')->cascadeOnDelete();
            $t->string('valor_avisado');
            $t->timestampTz('resultado_digitado_em', 6);
            $t->unsignedBigInteger('avisado_por');
            $t->timestampTz('avisado_em');
            $t->string('destinatario');
            $t->string('telefone');
            $t->timestampsTz();
            $t->unique('resultado_id');
        });
        Rls::isolarPorLaboratorio('avisos_valor_critico');
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_valor_critico');
        Schema::dropIfExists('laudos');
        Schema::dropIfExists('resultados');
    }
};

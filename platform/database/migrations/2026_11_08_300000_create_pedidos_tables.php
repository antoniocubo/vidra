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
        Schema::create('sequencias', function (Blueprint $table): void {
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('nome', 40);
            $table->unsignedBigInteger('valor')->default(0);
            $table->primary(['laboratorio_id', 'nome']);
        });
        Rls::isolarPorLaboratorio('sequencias');

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->unsignedInteger('protocolo');
            // Paciente e solicitante são do módulo de cada linha de produto: sem chave estrangeira de propósito.
            $table->uuid('paciente_id');
            $table->uuid('solicitante_id');
            $table->string('pagador_tipo', 20);
            $table->uuid('clinica_id')->nullable();
            $table->string('prioridade', 20);
            $table->text('observacao')->nullable();
            $table->timestamp('criado_em');
            $table->unsignedBigInteger('criado_por');
            $table->timestamps();
            $table->unique(['laboratorio_id', 'protocolo']);
            $table->index(['laboratorio_id', 'criado_em']);
            $table->index(['laboratorio_id', 'paciente_id']);
        });
        Rls::isolarPorLaboratorio('pedidos');

        Schema::create('pedido_itens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->uuid('exame_id');
            $table->string('codigo', 12);
            $table->string('nome');
            $table->string('setor', 40);
            $table->string('material');
            $table->unsignedInteger('preco_tabela_centavos');
            $table->unsignedTinyInteger('desconto_percentual')->default(0);
            $table->unsignedInteger('preco_centavos');
            $table->string('situacao', 30);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
            $table->unique(['pedido_id', 'exame_id']);
            $table->index(['laboratorio_id', 'situacao']);
        });
        Rls::isolarPorLaboratorio('pedido_itens');

        Schema::create('amostras', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('material');
            $table->string('situacao', 30);
            $table->timestamp('recebida_em')->nullable();
            $table->unsignedBigInteger('recebida_por')->nullable();
            $table->string('motivo_rejeicao', 30)->nullable();
            $table->text('motivo_texto')->nullable();
            $table->text('ressalva')->nullable();
            $table->string('recoleta_codigo', 20)->nullable();
            $table->timestamps();
            $table->unique(['laboratorio_id', 'codigo']);
            $table->index(['laboratorio_id', 'situacao', 'created_at']);
            $table->index(['laboratorio_id', 'recebida_em']);
        });
        Rls::isolarPorLaboratorio('amostras');

        Schema::create('amostra_itens', function (Blueprint $table): void {
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('amostra_id')->constrained('amostras')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('pedido_itens')->cascadeOnDelete();
            $table->primary(['amostra_id', 'item_id']);
        });
        Rls::isolarPorLaboratorio('amostra_itens');
    }

    public function down(): void
    {
        Schema::dropIfExists('amostra_itens');
        Schema::dropIfExists('amostras');
        Schema::dropIfExists('pedido_itens');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('sequencias');
    }
};

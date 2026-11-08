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
        Schema::create('clinicas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('nome_fantasia');
            $table->string('razao_social')->nullable();
            $table->string('cnpj', 14)->nullable();
            $table->string('cidade');
            $table->string('bairro')->nullable();
            $table->string('email_laudos')->nullable();
            $table->string('email_faturamento');
            $table->string('telefone_critico', 11)->nullable();
            $table->string('responsavel')->nullable();
            $table->text('observacao_coleta')->nullable();
            $table->unsignedTinyInteger('desconto')->default(0);
            $table->unsignedTinyInteger('dia_fechamento');
            $table->unsignedTinyInteger('dia_vencimento');
            $table->string('forma_pagamento');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->unique(['laboratorio_id', 'cnpj']);
            $table->index(['laboratorio_id', 'nome_fantasia']);
        });
        Rls::isolarPorLaboratorio('clinicas');
    }

    public function down(): void
    {
        Schema::dropIfExists('clinicas');
    }
};

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
        Schema::create('exames', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('codigo', 12);
            $table->string('nome');
            $table->string('setor');
            $table->string('material');
            $table->string('metodo')->nullable();
            $table->unsignedSmallInteger('prazo_horas');
            $table->unsignedBigInteger('preco_centavos');
            $table->boolean('ativo');
            $table->timestamps();
            $table->unique(['laboratorio_id', 'codigo']);
        });
        Rls::isolarPorLaboratorio('exames');
        Schema::create('analitos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('exame_id')->constrained('exames')->cascadeOnDelete();
            $table->string('nome');
            $table->string('unidade')->nullable();
            $table->unsignedTinyInteger('casas_decimais');
            $table->unsignedInteger('ordem');
            $table->timestamps();
        });
        Rls::isolarPorLaboratorio('analitos');
        Schema::create('faixas_referencia', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('analito_id')->constrained('analitos')->cascadeOnDelete();
            $table->string('perfil');
            foreach (['minimo', 'maximo', 'critico_baixo', 'critico_alto'] as $campo) {
                $table->decimal($campo, 14, 4)->nullable();
            }
            $table->timestamps();
            $table->unique(['analito_id', 'perfil']);
        });
        Rls::isolarPorLaboratorio('faixas_referencia');
    }

    public function down(): void
    {
        Schema::dropIfExists('faixas_referencia');
        Schema::dropIfExists('analitos');
        Schema::dropIfExists('exames');
    }
};

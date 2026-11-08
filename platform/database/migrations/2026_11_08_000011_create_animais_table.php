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
        Schema::create('animais', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('tutor_id')->constrained('tutores')->cascadeOnDelete();
            $table->string('nome');
            $table->string('especie');
            $table->string('raca')->nullable();
            $table->string('sexo');
            $table->date('nascimento');
            $table->boolean('nascimento_estimado');
            $table->integer('peso_gramas')->nullable();
            $table->string('castrado');
            $table->string('microchip', 15)->nullable();
            $table->foreignUuid('vinculo_veterinario_id')->nullable()->constrained('laboratorio_veterinario')->nullOnDelete();
            $table->index(['laboratorio_id', 'nome']);
            $table->timestamps();
        });
        Rls::isolarPorLaboratorio('animais');
    }

    public function down(): void
    {
        Schema::dropIfExists('animais');
    }
};

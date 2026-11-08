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
        Schema::create('clinica_veterinario', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignUuid('vinculo_id')->constrained('laboratorio_veterinario')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['clinica_id', 'vinculo_id']);
            $table->index('vinculo_id');
        });
        Rls::isolarPorLaboratorio('clinica_veterinario');
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica_veterinario');
    }
};

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
        Schema::create('laboratorio_veterinario', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('veterinario_id')->constrained('veterinarios')->cascadeOnDelete();
            $table->string('quem_paga');
            $table->string('telefone_critico', 11);
            $table->boolean('ve_pedidos_dos_colegas')->default(false);
            $table->string('recebe_laudo');
            $table->timestamps();
            $table->unique(['laboratorio_id', 'veterinario_id']);
            $table->index('veterinario_id');
        });
        Rls::isolarPorLaboratorio('laboratorio_veterinario');
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratorio_veterinario');
    }
};

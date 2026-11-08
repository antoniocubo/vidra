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
        Schema::create('tutores', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('nome');
            $table->string('celular', 11);
            $table->string('email')->nullable();
            $table->string('cpf', 11)->nullable();
            $table->string('cep', 8)->nullable();
            $table->timestampTz('email_autorizado_em')->nullable();
            $table->foreignId('email_autorizado_por')->nullable()->constrained('users');
            $table->unique(['laboratorio_id', 'celular']);
            $table->index(['laboratorio_id', 'nome']);
            $table->index(['laboratorio_id', 'cpf']);
            $table->timestamps();
        });
        Rls::isolarPorLaboratorio('tutores');
    }

    public function down(): void
    {
        Schema::dropIfExists('tutores');
    }
};

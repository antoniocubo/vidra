<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Acesso do veterinário (vet.vidralab.com.br). A conta é da pessoa (global, como
| `veterinarios`) e não dá acesso a nada sozinha: cada laboratório que ela vê sai
| de um vínculo em `laboratorio_veterinario`, conferido a cada requisição.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_veterinario', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('veterinario_id')->unique()->constrained('veterinarios')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestampsTz();
        });

        // Global pelo mesmo motivo de `convites`: o veterinário aceita antes de ter sessão.
        Schema::create('convites_veterinario', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignUuid('veterinario_id')->constrained('veterinarios')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('convidado_por')->constrained('users');
            $table->timestampTz('expira_em');
            $table->timestampTz('aceito_em')->nullable();
            $table->timestampTz('cancelado_em')->nullable();
            $table->timestampsTz();
            $table->index(['laboratorio_id', 'veterinario_id']);
        });

        Schema::create('password_reset_tokens_vet', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens_vet');
        Schema::dropIfExists('convites_veterinario');
        Schema::dropIfExists('contas_veterinario');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global: o destinatário acessa o convite antes de pertencer ao laboratório.
        Schema::create('convites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->string('nome');
            $table->string('email');
            $table->string('papel');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('convidado_por')->constrained('users');
            $table->timestampTz('expira_em');
            $table->timestampTz('aceito_em')->nullable();
            $table->timestampTz('cancelado_em')->nullable();
            $table->timestampsTz();
            $table->index(['laboratorio_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convites');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratorios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('cnpj', 14)->unique();
            $table->string('telefone', 11);
            $table->string('cep', 8);
            $table->string('cidade');
            $table->string('uf', 2);
            $table->string('endereco');
            $table->string('linha');
            $table->string('plano');
            $table->string('cobranca');
            $table->timestampTz('teste_termina_em');
            $table->string('rt_nome');
            $table->string('rt_conselho', 20);
            $table->string('rt_numero', 20);
            $table->string('rt_uf', 2);
            $table->string('rt_titulo')->nullable();
            $table->string('rt_email')->nullable();
            $table->timestampsTz();
        });
        Schema::create('laboratorio_usuario', function (Blueprint $table): void {
            $table->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('papel');
            $table->boolean('e_rt')->default(false);
            $table->primary(['laboratorio_id', 'user_id']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('celular', 11)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratorio_usuario');
        Schema::dropIfExists('laboratorios');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('celular');
        });
    }
};

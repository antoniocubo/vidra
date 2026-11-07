<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veterinarios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tratamento', 10);
            $table->string('nome');
            $table->string('crmv_numero', 6);
            $table->char('crmv_uf', 2);
            $table->string('email');
            $table->string('celular', 11);
            $table->string('telefone_critico', 11);
            $table->string('especialidade')->nullable();
            $table->timestamps();

            $table->unique(['crmv_uf', 'crmv_numero']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veterinarios');
    }
};

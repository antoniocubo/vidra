<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veterinarios', function (Blueprint $table): void {
            $table->dropColumn('telefone_critico');
        });
    }

    public function down(): void
    {
        Schema::table('veterinarios', function (Blueprint $table): void {
            $table->string('telefone_critico', 11)->default('');
        });
    }
};

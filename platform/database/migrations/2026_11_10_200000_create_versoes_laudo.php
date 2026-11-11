<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Vidra\Shared\Infrastructure\Tenancy\Rls;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laudos', function (Blueprint $t): void {
            $t->unsignedInteger('versao')->default(1);
            foreach (['retificacao', 'reaberto'] as $prefixo) {
                $t->string($prefixo.'_motivo', 500)->nullable();
                $t->unsignedBigInteger($prefixo.'_por')->nullable();
                $t->timestampTz($prefixo.'_em')->nullable();
            }
        });
        Schema::create('versoes_laudo', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('laboratorio_id')->constrained('laboratorios')->cascadeOnDelete();
            $t->foreignUuid('laudo_id')->constrained('laudos')->cascadeOnDelete();
            $t->unsignedInteger('versao');
            $t->unsignedBigInteger('liberado_por');
            foreach (['usuario_nome', 'rt_nome', 'rt_registro'] as $c) {
                $t->string($c);
            }
            $t->timestampTz('liberado_em');
            $t->text('observacao')->nullable();
            $t->string('retificacao_motivo', 500)->nullable();
            $t->timestampTz('retificacao_em')->nullable();
            $t->string('pdf_caminho')->nullable();
            $t->text('conteudo')->nullable();
            $t->timestampsTz();
            $t->unique(['laudo_id', 'versao']);
        });
        // Preserva os laudos e PDFs emitidos antes do versionamento.
        DB::table('laudos')->orderBy('id')->each(function (object $l): void {
            DB::table('versoes_laudo')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $l->laboratorio_id, 'laudo_id' => $l->id, 'versao' => 1, 'liberado_por' => $l->liberado_por, 'usuario_nome' => $l->usuario_nome, 'rt_nome' => $l->rt_nome, 'rt_registro' => $l->rt_registro, 'liberado_em' => $l->liberado_em, 'observacao' => $l->observacao, 'pdf_caminho' => $l->pdf_caminho, 'created_at' => now(), 'updated_at' => now()]);
        });
        Rls::isolarPorLaboratorio('versoes_laudo');
    }

    public function down(): void
    {
        Schema::dropIfExists('versoes_laudo');
        Schema::table('laudos', function (Blueprint $t): void {
            $t->dropColumn(['versao', 'retificacao_motivo', 'retificacao_por', 'retificacao_em', 'reaberto_motivo', 'reaberto_por', 'reaberto_em']);
        });
    }
};

<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure\Tenancy;

use Illuminate\Support\Facades\DB;

/**
 * Segunda defesa da tenancy: Row Level Security no Postgres. Use nas migrations,
 * logo depois de criar uma tabela com `laboratorio_id`. No SQLite não faz nada.
 */
final class Rls
{
    private const string LABORATORIO_DA_SESSAO = "nullif(current_setting('app.laboratorio_id', true), '')::uuid";

    public static function isolarPorLaboratorio(string $tabela): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $condicao = 'laboratorio_id = '.self::LABORATORIO_DA_SESSAO;

        DB::statement("alter table {$tabela} enable row level security");
        // force: o dono da tabela também obedece. Só superusuário e BYPASSRLS passam.
        DB::statement("alter table {$tabela} force row level security");
        DB::statement("create policy isolamento_laboratorio on {$tabela} using ({$condicao}) with check ({$condicao})");
    }
}

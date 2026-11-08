<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CenarioDeBancada
{
    /** @return array<string,string> */
    public static function criar(string $lab): array
    {
        $c = CenarioDePedidos::criar($lab);
        $id = (string) Str::uuid7();
        DB::connection(config()->string('vidra.conexao_sistema'))->table('analitos')->insert(['id' => $id, 'laboratorio_id' => $lab, 'exame_id' => $c['bioquimico'], 'nome' => 'Potássio', 'unidade' => 'mEq/L', 'casas_decimais' => 2, 'ordem' => 0, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['cao.adulto', 'cao.idoso'] as $perfil) {
            DB::connection(config()->string('vidra.conexao_sistema'))->table('faixas_referencia')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $lab, 'analito_id' => $id, 'perfil' => $perfil, 'minimo' => '3', 'maximo' => '5', 'critico_baixo' => '2', 'critico_alto' => '8', 'created_at' => now(), 'updated_at' => now()]);
        }

        return [...$c, 'analito' => $id];
    }
}

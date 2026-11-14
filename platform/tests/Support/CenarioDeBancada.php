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
        $id = $c['analito_bioquimico']; // o Potássio que o CenarioDePedidos já criou
        foreach (['cao.adulto', 'cao.idoso'] as $perfil) {
            DB::connection(config()->string('vidra.conexao_sistema'))->table('faixas_referencia')->insert(['id' => (string) Str::uuid7(), 'laboratorio_id' => $lab, 'analito_id' => $id, 'perfil' => $perfil, 'minimo' => '3', 'maximo' => '5', 'critico_baixo' => '2', 'critico_alto' => '8', 'created_at' => now(), 'updated_at' => now()]);
        }

        return [...$c, 'analito' => $id];
    }
}

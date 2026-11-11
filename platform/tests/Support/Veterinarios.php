<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\ContaVeterinario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Veterinário com conta no acesso vet e vínculo nos laboratórios dados. */
final class Veterinarios
{
    public const string SENHA = 'senha-do-vet-2026';

    /** @param list<string> $laboratorios */
    public static function comContaEm(array $laboratorios, bool $vePedidosDosColegas = false): ContaVeterinario
    {
        $db = DB::connection(config()->string('vidra.conexao_sistema'));
        $id = (string) Str::uuid7();
        $numero = (string) random_int(100000, 999999);
        $email = "vet{$numero}@exemplo.com.br";
        $db->table('veterinarios')->insert([
            'id' => $id, 'tratamento' => 'dra', 'nome' => 'Lia Prado', 'crmv_numero' => $numero, 'crmv_uf' => 'SP', 'email' => $email,
            'celular' => '19999990000', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($laboratorios as $laboratorio) {
            self::vincular($id, $laboratorio, $vePedidosDosColegas);
        }

        return ContaVeterinario::query()->create(['veterinario_id' => $id, 'email' => $email, 'password' => self::SENHA]);
    }

    public static function vincular(string $veterinarioId, string $laboratorio, bool $vePedidosDosColegas = false): string
    {
        $vinculo = (string) Str::uuid7();
        DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_veterinario')->insert([
            'id' => $vinculo, 'laboratorio_id' => $laboratorio, 'veterinario_id' => $veterinarioId, 'quem_paga' => 'clinica', 'telefone_critico' => '1932410099',
            've_pedidos_dos_colegas' => $vePedidosDosColegas, 'recebe_laudo' => 'cada_laudo', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $vinculo;
    }
}

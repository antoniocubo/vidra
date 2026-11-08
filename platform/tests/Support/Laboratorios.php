<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Laboratórios e usuários prontos para os testes que precisam de tenancy. */
final class Laboratorios
{
    public static function criar(string $nome = 'Laboratório Alfa'): string
    {
        $id = (string) Str::uuid7();
        DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->insert([
            'id' => $id, 'nome' => $nome, 'cnpj' => substr(str_pad((string) random_int(0, PHP_INT_MAX), 14, '0'), 0, 14),
            'telefone' => '1932100000', 'cep' => '13010000', 'cidade' => 'Campinas', 'uf' => 'SP', 'endereco' => 'Rua das Amostras, 100',
            'linha' => 'pet', 'plano' => 'laboratorio', 'cobranca' => 'mensal', 'teste_termina_em' => now()->addDays(14),
            'rt_nome' => 'Marina Alves', 'rt_conselho' => 'CRMV', 'rt_numero' => '12345', 'rt_uf' => 'SP',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public static function usuarioEm(string $laboratorioId, string $papel = 'admin', ?User $usuario = null): User
    {
        $usuario ??= User::factory()->create();
        DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_usuario')->insert([
            'laboratorio_id' => $laboratorioId, 'user_id' => $usuario->id, 'papel' => $papel, 'e_rt' => false,
        ]);

        return $usuario;
    }

    /** Atalho para o caso comum: um usuário num laboratório só. */
    public static function usuario(): User
    {
        return self::usuarioEm(self::criar());
    }
}

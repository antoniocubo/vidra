<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dados mínimos para usar o app localmente: dois laboratórios e um usuário em
 * ambos (para testar a escolha e a troca de laboratório).
 * Uso: php artisan db:seed --class=DesenvolvimentoSeeder
 * Login: bruno.teste@vidra.test / vidra-teste-2026 (só ambiente local).
 */
final class DesenvolvimentoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()->firstOrCreate(
            ['email' => 'bruno.teste@vidra.test'],
            ['name' => 'Bruno Teste', 'password' => bcrypt('vidra-teste-2026'), 'email_verified_at' => now()],
        );

        foreach (['Laboratório Alfa Vet' => 'admin', 'Lab Beta Diagnóstico' => 'bancada'] as $nome => $papel) {
            $conexao = DB::connection(config()->string('vidra.conexao_sistema'));
            $id = $conexao->table('laboratorios')->where('nome', $nome)->value('id');
            if ($id === null) {
                $id = (string) Str::uuid7();
                $conexao->table('laboratorios')->insert([
                    'id' => $id, 'nome' => $nome, 'cnpj' => str_pad((string) random_int(1, 99_999_999), 14, '0', STR_PAD_LEFT),
                    'telefone' => '1932100000', 'cep' => '13010000', 'cidade' => 'Campinas', 'uf' => 'SP', 'endereco' => 'Rua das Amostras, 100',
                    'linha' => 'pet', 'plano' => 'laboratorio', 'cobranca' => 'mensal', 'teste_termina_em' => now()->addDays(14),
                    'rt_nome' => 'Marina Alves', 'rt_conselho' => 'CRMV', 'rt_numero' => '12345', 'rt_uf' => 'SP',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $conexao->table('laboratorio_usuario')->insertOrIgnore([
                'laboratorio_id' => $id, 'user_id' => $usuario->id, 'papel' => $papel, 'e_rt' => $papel === 'admin',
            ]);
        }
    }
}

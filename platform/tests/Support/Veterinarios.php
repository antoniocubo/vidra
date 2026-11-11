<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\ContaVeterinario;
use Closure;
use DateTimeImmutable;
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

    public static function pedido(string $laboratorio, string $vinculo): string
    {
        $db = DB::connection(config()->string('vidra.conexao_sistema'));
        if (! $db->table('exames')->where('laboratorio_id', $laboratorio)->exists()) {
            CenarioDePedidos::criar($laboratorio);
        }
        $animal = (string) $db->table('animais')->where('laboratorio_id', $laboratorio)->value('id');
        $exame = (string) $db->table('exames')->where('laboratorio_id', $laboratorio)->value('id');
        $usuario = Laboratorios::usuarioEm($laboratorio);

        return app(\Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio::class)->executarComo($laboratorio, fn (): string => app(\Vidra\Pedidos\Application\AbrirPedido\AbrirPedido::class)(new \Vidra\Pedidos\Application\AbrirPedido\AbrirPedidoInput($animal, $vinculo, 'responsavel', null, 'rotina', [$exame], null, $usuario->id))->pedidoId);
    }

    public static function vinculoDa(ContaVeterinario $conta, string $laboratorio): string
    {
        return (string) DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_veterinario')->where('veterinario_id', $conta->veterinario_id)->where('laboratorio_id', $laboratorio)->value('id');
    }

    /** Repositório em memória para exercitar as regras do convite sem persistência. */
    public static function convitesEmMemoria(): \Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario
    {
        return new class implements \Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario
        {
            /** @var array<string, \Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario> */
            private array $convites = [];

            /** @var array<string, string> */
            private array $contas = [];

            public function transacao(Closure $acao): mixed
            {
                return $acao();
            }

            public function nextIdentity(): string
            {
                return 'convite-'.count($this->convites);
            }

            public function token(): string
            {
                return str_repeat('x', 48);
            }

            public function pessoa(string $id): array
            {
                return ['nome' => 'Lia Prado', 'email' => 'lia@exemplo.com.br'];
            }

            public function nomeDoLaboratorio(string $id): string
            {
                return 'Laboratório Alfa';
            }

            public function temVinculo(string $laboratorio, string $veterinario): bool
            {
                return true;
            }

            public function emailDaConta(string $veterinario): ?string
            {
                return $this->contas[$veterinario] ?? null;
            }

            public function criarConta(string $veterinario, string $email, string $senha): int
            {
                $this->contas[$veterinario] = $email;

                return count($this->contas);
            }

            public function salvar(\Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario $convite): void
            {
                $this->convites[$convite->tokenHash] = $convite;
            }

            public function peloHash(string $hash): ?\Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario
            {
                return $this->convites[$hash] ?? null;
            }

            public function aceitar(string $id, DateTimeImmutable $agora): void
            {
                foreach ($this->convites as $hash => $c) {
                    if ($c->id === $id) {
                        $this->convites[$hash] = \Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario::restaurar($c->id, $c->laboratorioId, $c->veterinarioId, $hash, $c->convidadoPor, $c->expiraEm, $agora, null);
                    }
                }
            }
        };
    }
}

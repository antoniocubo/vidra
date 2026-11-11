<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Acesso;

use App\Models\ContaVeterinario;
use Closure;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Veterinaria\Domain\Acesso\AcessoVeterinarioInvalido;
use Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\ConvitesDoVeterinario;

final class EloquentConvitesDoVeterinario implements ConvitesDoVeterinario
{
    private function db(): Connection
    {
        return DB::connection(config()->string('vidra.conexao_sistema'));
    }

    public function transacao(Closure $acao): mixed
    {
        return $this->db()->transaction($acao);
    }

    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function token(): string
    {
        return Str::random(48);
    }

    public function salvar(ConviteDoVeterinario $convite): void
    {
        $t = $this->db()->table('convites_veterinario');
        $t->where('laboratorio_id', $convite->laboratorioId)->where('veterinario_id', $convite->veterinarioId)->whereNull('aceito_em')->whereNull('cancelado_em')->update(['cancelado_em' => now(), 'updated_at' => now()]);
        $this->db()->table('convites_veterinario')->insert(['id' => $convite->id, 'laboratorio_id' => $convite->laboratorioId, 'veterinario_id' => $convite->veterinarioId, 'token_hash' => $convite->tokenHash, 'convidado_por' => $convite->convidadoPor, 'expira_em' => $convite->expiraEm, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function peloHash(string $hash): ?ConviteDoVeterinario
    {
        // Todas as operações bloqueiam primeiro a pessoa, evitando inversão de locks.
        $pessoa = $this->db()->table('convites_veterinario')->where('token_hash', $hash)->value('veterinario_id');
        if (! is_string($pessoa)) {
            return null;
        }
        $this->pessoa($pessoa);
        $r = $this->db()->table('convites_veterinario')->where('token_hash', $hash)->lockForUpdate()->first();
        if ($r === null) {
            return null;
        }

        /** @var object{id:string,laboratorio_id:string,veterinario_id:string,token_hash:string,convidado_por:int,expira_em:string,aceito_em:?string,cancelado_em:?string} $r */
        return ConviteDoVeterinario::restaurar($r->id, $r->laboratorio_id, $r->veterinario_id, $r->token_hash, $r->convidado_por, new DateTimeImmutable($r->expira_em), $r->aceito_em === null ? null : new DateTimeImmutable($r->aceito_em), $r->cancelado_em === null ? null : new DateTimeImmutable($r->cancelado_em));
    }

    public function aceitar(string $id, DateTimeImmutable $agora): void
    {
        $this->db()->table('convites_veterinario')->where('id', $id)->update(['aceito_em' => $agora, 'updated_at' => $agora]);
    }

    public function pessoa(string $id): array
    {
        $r = $this->db()->table('veterinarios')->where('id', $id)->lockForUpdate()->first() ?? throw new AcessoVeterinarioInvalido('Veterinário não encontrado.');

        return ['nome' => (string) $r->nome, 'email' => (string) $r->email];
    }

    public function emailDaConta(string $veterinario): ?string
    {
        $email = $this->db()->table('contas_veterinario')->where('veterinario_id', $veterinario)->value('email');

        return is_string($email) ? $email : null;
    }

    public function criarConta(string $veterinario, string $email, string $senha): int
    {
        if ($this->db()->table('contas_veterinario')->where('email', $email)->exists()) {
            throw new AcessoVeterinarioInvalido('Você já tem acesso. Entre com sua senha.');
        }
        $conta = new ContaVeterinario;
        $conta->setConnection(config()->string('vidra.conexao_sistema'));
        $conta->fill(['veterinario_id' => $veterinario, 'email' => $email, 'password' => $senha])->save();

        return $conta->id;
    }

    public function nomeDoLaboratorio(string $id): string
    {
        return (string) $this->db()->table('laboratorios')->where('id', $id)->value('nome');
    }

    public function temVinculo(string $laboratorio, string $veterinario): bool
    {
        return $this->db()->table('laboratorio_veterinario')->where('laboratorio_id', $laboratorio)->where('veterinario_id', $veterinario)->exists();
    }
}

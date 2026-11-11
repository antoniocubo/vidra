<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Vidra\Acesso\Domain\Equipe;
use Vidra\Acesso\Domain\MembroDaEquipe;
use Vidra\Acesso\Domain\Papel;

final class EloquentEquipe implements Equipe
{
    private function tabela(string $laboratorioId): Builder
    {
        return DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorio_usuario')->where('laboratorio_usuario.laboratorio_id', $laboratorioId);
    }

    public function listar(string $laboratorioId): array
    {
        return array_values($this->tabela($laboratorioId)->join('users', 'users.id', '=', 'laboratorio_usuario.user_id')->orderBy('users.name')->get(['users.id', 'users.name', 'users.email', 'laboratorio_usuario.papel', 'laboratorio_usuario.e_rt'])->map(function (object $linha): MembroDaEquipe {
            /** @var object{id:int,name:string,email:string,papel:string,e_rt:bool} $linha */
            return new MembroDaEquipe((int) $linha->id, $linha->name, $linha->email, Papel::from($linha->papel), (bool) $linha->e_rt);
        })->all());
    }

    public function contemEmail(string $laboratorioId, string $email): bool
    {
        return $this->tabela($laboratorioId)->join('users', 'users.id', '=', 'laboratorio_usuario.user_id')->whereRaw('lower(users.email) = ?', [strtolower(trim($email))])->exists();
    }

    public function adicionar(string $laboratorioId, int $usuarioId, Papel $papel): void
    {
        $this->tabela($laboratorioId)->insert(['laboratorio_id' => $laboratorioId, 'user_id' => $usuarioId, 'papel' => $papel->value, 'e_rt' => $papel === Papel::Rt]);
    }

    public function trocarPapel(string $laboratorioId, int $usuarioId, Papel $papel, bool $eRt): void
    {
        $this->tabela($laboratorioId)->where('user_id', $usuarioId)->update(['papel' => $papel->value, 'e_rt' => $eRt]);
    }

    public function remover(string $laboratorioId, int $usuarioId): void
    {
        $this->tabela($laboratorioId)->where('user_id', $usuarioId)->delete();
    }

    public function nomeDoLaboratorio(string $laboratorioId): string
    {
        return (string) DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->where('id', $laboratorioId)->value('nome');
    }

    public function nomeDoUsuario(int $usuarioId): string
    {
        return (string) DB::connection(config()->string('vidra.conexao_sistema'))->table('users')->where('id', $usuarioId)->value('name');
    }
}

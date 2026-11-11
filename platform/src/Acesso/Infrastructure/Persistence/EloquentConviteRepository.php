<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\ConviteRepository;
use Vidra\Acesso\Domain\Papel;

final class EloquentConviteRepository implements ConviteRepository
{
    private function tabela(): Builder
    {
        return DB::connection(config()->string('vidra.conexao_sistema'))->table('convites');
    }

    public function nextIdentity(): string
    {
        return (string) Str::uuid7();
    }

    public function save(Convite $convite): void
    {
        $this->tabela()->where('laboratorio_id', $convite->laboratorioId)->updateOrInsert(['id' => $convite->id, 'laboratorio_id' => $convite->laboratorioId], [
            'nome' => $convite->nome, 'email' => $convite->email, 'papel' => $convite->papel->value, 'token_hash' => $convite->tokenHash,
            'convidado_por' => $convite->convidadoPor, 'expira_em' => $convite->expiraEm, 'aceito_em' => $convite->aceitoEm, 'cancelado_em' => $convite->canceladoEm,
            'updated_at' => now(),
            'created_at' => $this->tabela()->where('laboratorio_id', $convite->laboratorioId)->where('id', $convite->id)->value('created_at') ?? now(),
        ]);
    }

    public function findById(string $laboratorioId, string $id): ?Convite
    {
        $linha = $this->tabela()->where('laboratorio_id', $laboratorioId)->where('id', $id)->first();

        return $linha === null ? null : $this->restaurar($linha);
    }

    public function findByTokenHash(string $hash): ?Convite
    {
        $linha = $this->tabela()->where('token_hash', $hash)->first();

        return $linha === null ? null : $this->restaurar($linha);
    }

    public function pendentes(string $laboratorioId): array
    {
        return array_values($this->tabela()->where('laboratorio_id', $laboratorioId)->whereNull('aceito_em')->whereNull('cancelado_em')->orderBy('nome')->get()->map(fn (object $linha): Convite => $this->restaurar($linha))->all());
    }

    private function restaurar(object $linha): Convite
    {
        /** @var object{id:string,laboratorio_id:string,nome:string,email:string,papel:string,token_hash:string,convidado_por:int,expira_em:string,aceito_em:?string,cancelado_em:?string} $linha */
        return Convite::restaurar($linha->id, $linha->laboratorio_id, $linha->nome, $linha->email, Papel::from($linha->papel), $linha->token_hash, (int) $linha->convidado_por, new DateTimeImmutable($linha->expira_em), $linha->aceito_em === null ? null : new DateTimeImmutable($linha->aceito_em), $linha->cancelado_em === null ? null : new DateTimeImmutable($linha->cancelado_em));
    }
}

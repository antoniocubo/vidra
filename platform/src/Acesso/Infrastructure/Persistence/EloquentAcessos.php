<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Vidra\Acesso\Domain\Acessos;
use Vidra\Acesso\Domain\LaboratorioDoUsuario;
use Vidra\Acesso\Domain\LaboratorioId;

final class EloquentAcessos implements Acessos
{
    public function laboratoriosDoUsuario(int $usuarioId): array
    {
        /** @var list<object{id: string, nome: string, papel: string}> $linhas */
        $linhas = DB::connection(config()->string('vidra.conexao_sistema'))
            ->table('laboratorio_usuario')
            ->join('laboratorios', 'laboratorios.id', '=', 'laboratorio_usuario.laboratorio_id')
            ->where('laboratorio_usuario.user_id', $usuarioId)
            ->orderBy('laboratorios.nome')
            ->get(['laboratorios.id', 'laboratorios.nome', 'laboratorio_usuario.papel'])
            ->all();

        return array_map(
            static fn (object $linha): LaboratorioDoUsuario => new LaboratorioDoUsuario(new LaboratorioId($linha->id), $linha->nome, $linha->papel),
            $linhas,
        );
    }
}

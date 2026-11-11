<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Persistence;

use Closure;
use Illuminate\Support\Facades\DB;
use Vidra\Acesso\Domain\TransacaoDaEquipe;

final class TransacaoEloquentDaEquipe implements TransacaoDaEquipe
{
    public function executar(string $laboratorioId, Closure $acao): mixed
    {
        $conexao = DB::connection(config()->string('vidra.conexao_sistema'));

        return $conexao->transaction(function () use ($conexao, $laboratorioId, $acao): mixed {
            // Um bloqueio estável também protege o primeiro convite e o último administrador.
            $conexao->table('laboratorios')->where('id', $laboratorioId)->lockForUpdate()->firstOrFail();

            return $acao();
        });
    }
}
